<?php
/**
 * Workload repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use DateTimeImmutable;

final class WorkloadRepository extends AbstractRepository {
	/**
	 * Get workload data for the current user.
	 *
	 * @param array<string, mixed> $filters Workload filters.
	 * @return array<string, mixed>
	 */
	public function get_for_current_user( array $filters = array() ): array {
		$week_start     = $this->normalize_week_start( isset( $filters['week_start'] ) ? (string) $filters['week_start'] : '' );
		$week_end       = $week_start->modify( '+6 days' );
		$user_id        = get_current_user_id();
		$priority       = sanitize_key( (string) ( $filters['priority'] ?? '' ) );
		$status         = sanitize_key( (string) ( $filters['status'] ?? '' ) );
		$project_filter = isset( $filters['project_id'] ) ? (string) $filters['project_id'] : '';
		$person_filter  = max( 0, (int) ( $filters['person_user_id'] ?? 0 ) );
		$rows           = $this->build_rows(
			$this->get_open_tasks( $user_id, $week_start, $week_end, $priority, $status, $project_filter, $person_filter ),
			$week_start,
			$week_end
		);

		return array(
			'week'    => array(
				'start' => $week_start->format( 'Y-m-d' ),
				'end'   => $week_end->format( 'Y-m-d' ),
				'label' => sprintf(
					/* translators: 1: week start date, 2: week end date */
					__( '%1$s to %2$s', 'coordina' ),
					wp_date( 'M j', $week_start->getTimestamp() ),
					wp_date( 'M j, Y', $week_end->getTimestamp() )
				),
			),
			'filters' => array(
				'status'         => $status,
				'priority'       => $priority,
				'project_id'     => $project_filter,
				'person_user_id' => $person_filter,
			),
			'summary' => $this->build_summary( $rows ),
			'rows'    => $rows,
		);
	}

	/**
	 * Fetch open tasks in manager scope.
	 *
	 * @param int               $user_id Current user id.
	 * @param DateTimeImmutable $week_start Week start date.
	 * @param DateTimeImmutable $week_end Week end date.
	 * @param string            $priority Priority filter.
	 * @param string            $status Status filter.
	 * @param string            $project_filter Project filter.
	 * @param int               $person_filter Person filter.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_open_tasks( int $user_id, DateTimeImmutable $week_start, DateTimeImmutable $week_end, string $priority, string $status, string $project_filter, int $person_filter ): array {
		unset( $week_start, $week_end );

		$table  = $this->table( 'tasks' );
		$where  = array(
			'(project_id IN (SELECT id FROM ' . $this->table( 'projects' ) . ' WHERE manager_user_id = %d) OR assignee_user_id = %d OR reporter_user_id = %d)',
			"status NOT IN ('done', 'cancelled')",
		);
		$params = array( $user_id, $user_id, $user_id );

		if ( '' !== $status ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}

		if ( '' !== $priority ) {
			$where[]  = 'priority = %s';
			$params[] = $priority;
		}

		if ( '' !== $project_filter ) {
			if ( '0' === $project_filter ) {
				$where[] = 'project_id = 0';
			} else {
				$where[]  = 'project_id = %d';
				$params[] = max( 0, (int) $project_filter );
			}
		}

		if ( $person_filter > 0 ) {
			$where[]  = 'assignee_user_id = %d';
			$params[] = $person_filter;
		}

		$sql  = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY blocked DESC, due_date ASC, priority DESC, updated_at DESC';
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $params ) );
		$today = wp_date( 'Y-m-d' );

		return array_map(
			function ( $row ) use ( $today ): array {
				$item       = $this->row_to_array( $row );
				$project_id = (int) ( $item['project_id'] ?? 0 );
				$due_date   = (string) ( $item['due_date'] ?? '' );
				$due_key    = '' !== $due_date ? substr( $due_date, 0, 10 ) : '';

				return array(
					'id'            => (int) ( $item['id'] ?? 0 ),
					'title'         => (string) ( $item['title'] ?? '' ),
					'status'        => (string) ( $item['status'] ?? 'new' ),
					'priority'      => (string) ( $item['priority'] ?? 'normal' ),
					'dueDate'       => $due_date,
					'dueKey'        => $due_key,
					'isOverdue'     => '' !== $due_key && $due_key < $today,
					'isBlocked'     => ! empty( $item['blocked'] ) || 'blocked' === ( $item['status'] ?? '' ),
					'assigneeId'    => (int) ( $item['assignee_user_id'] ?? 0 ),
					'assigneeLabel' => $this->get_user_label( (int) ( $item['assignee_user_id'] ?? 0 ) ),
					'projectId'     => $project_id,
					'projectLabel'  => $this->get_project_label( $project_id ),
					'route'         => $project_id > 0
						? array(
							'page'        => 'coordina-task',
							'task_id'     => (int) ( $item['id'] ?? 0 ),
							'project_id'  => $project_id,
							'project_tab' => 'work',
						)
						: array(
							'page'    => 'coordina-task',
							'task_id' => (int) ( $item['id'] ?? 0 ),
						),
				);
			},
			$rows ?: array()
		);
	}

	/**
	 * Group workload rows by person.
	 *
	 * @param array<int, array<string, mixed>> $tasks Open tasks.
	 * @param DateTimeImmutable                $week_start Week start.
	 * @param DateTimeImmutable                $week_end Week end.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_rows( array $tasks, DateTimeImmutable $week_start, DateTimeImmutable $week_end ): array {
		$rows = array();

		foreach ( $tasks as $task ) {
			$key = (string) ( $task['assigneeId'] ?: 0 );

			if ( ! isset( $rows[ $key ] ) ) {
				$rows[ $key ] = array(
					'userId'       => (int) $task['assigneeId'],
					'personLabel'  => $task['assigneeLabel'] ?: __( 'Unassigned', 'coordina' ),
					'openTasks'    => 0,
					'overdue'      => 0,
					'dueThisWeek'  => 0,
					'blocked'      => 0,
					'urgent'       => 0,
					'highPriority' => 0,
					'loadScore'    => 0,
					'pressure'     => 'low',
					'tasks'        => array(),
				);
			}

			$rows[ $key ]['openTasks']++;

			if ( ! empty( $task['isOverdue'] ) ) {
				$rows[ $key ]['overdue']++;
			}

			if ( ! empty( $task['dueKey'] ) && $task['dueKey'] >= $week_start->format( 'Y-m-d' ) && $task['dueKey'] <= $week_end->format( 'Y-m-d' ) ) {
				$rows[ $key ]['dueThisWeek']++;
			}

			if ( ! empty( $task['isBlocked'] ) ) {
				$rows[ $key ]['blocked']++;
			}

			if ( 'urgent' === $task['priority'] ) {
				$rows[ $key ]['urgent']++;
			}

			if ( in_array( $task['priority'], array( 'high', 'urgent' ), true ) ) {
				$rows[ $key ]['highPriority']++;
			}

			$rows[ $key ]['tasks'][] = $task;
		}

		foreach ( $rows as &$row ) {
			usort(
				$row['tasks'],
				static function ( array $left, array $right ): int {
					$weight = static function ( array $task ): int {
						$score = 0;

						if ( ! empty( $task['isOverdue'] ) ) {
							$score += 4;
						}

						if ( ! empty( $task['isBlocked'] ) ) {
							$score += 3;
						}

						if ( 'urgent' === ( $task['priority'] ?? '' ) ) {
							$score += 2;
						}

						if ( 'high' === ( $task['priority'] ?? '' ) ) {
							$score += 1;
						}

						return $score;
					};

					$comparison = $weight( $right ) <=> $weight( $left );

					if ( 0 !== $comparison ) {
						return $comparison;
					}

					return strcmp( (string) ( $left['dueDate'] ?? '' ), (string) ( $right['dueDate'] ?? '' ) );
				}
			);

			$row['loadScore'] = (int) $row['openTasks'] + ( (int) $row['overdue'] * 2 ) + ( (int) $row['blocked'] * 2 ) + (int) $row['highPriority'] + ( (int) $row['urgent'] * 2 );
			$row['pressure']  = $this->pressure_label( (int) $row['loadScore'] );
			$row['tasks']     = array_slice( $row['tasks'], 0, 4 );
		}
		unset( $row );

		$rows = array_values( $rows );

		usort(
			$rows,
			static function ( array $left, array $right ): int {
				$comparison = (int) $right['loadScore'] <=> (int) $left['loadScore'];

				if ( 0 !== $comparison ) {
					return $comparison;
				}

				return strcmp( (string) $left['personLabel'], (string) $right['personLabel'] );
			}
		);

		return $rows;
	}

	/**
	 * Build summary metrics.
	 *
	 * @param array<int, array<string, mixed>> $rows Workload rows.
	 * @return array<string, int>
	 */
	private function build_summary( array $rows ): array {
		return array(
			'people'       => count( $rows ),
			'overloaded'   => count( array_filter( $rows, static function ( array $row ): bool { return 'high' === $row['pressure']; } ) ),
			'watchList'    => count( array_filter( $rows, static function ( array $row ): bool { return 'medium' === $row['pressure']; } ) ),
			'unassigned'   => count( array_filter( $rows, static function ( array $row ): bool { return 0 === (int) $row['userId']; } ) ),
			'overdueTasks' => array_sum( array_map( static function ( array $row ): int { return (int) $row['overdue']; }, $rows ) ),
			'blockedTasks' => array_sum( array_map( static function ( array $row ): int { return (int) $row['blocked']; }, $rows ) ),
		);
	}

	/**
	 * Normalize the selected week start.
	 *
	 * @param string $value Raw week start.
	 * @return DateTimeImmutable
	 */
	private function normalize_week_start( string $value ): DateTimeImmutable {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		if ( false === $date ) {
			$date = new DateTimeImmutable( 'now', wp_timezone() );
		}

		return $date->modify( 'monday this week' );
	}

	/**
	 * Translate a load score into a pressure label.
	 *
	 * @param int $score Load score.
	 * @return string
	 */
	private function pressure_label( int $score ): string {
		if ( $score >= 12 ) {
			return 'high';
		}

		if ( $score >= 6 ) {
			return 'medium';
		}

		return 'low';
	}

	/**
	 * Resolve a user label.
	 *
	 * @param int $user_id User id.
	 * @return string
	 */
	protected function get_user_label( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}

		return get_userdata( $user_id )->display_name ?? '';
	}

	/**
	 * Resolve a project label.
	 *
	 * @param int $project_id Project id.
	 * @return string
	 */
	protected function get_project_label( int $project_id, string $fallback = '' ): string {
		if ( $project_id <= 0 ) {
			return __( 'Standalone', 'coordina' );
		}

		$title = $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'projects' ) . ' WHERE id = %d', $project_id ) );

		return $title ? (string) $title : __( 'Project task', 'coordina' );
	}
}
