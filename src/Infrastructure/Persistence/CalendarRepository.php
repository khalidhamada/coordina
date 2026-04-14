<?php
/**
 * Calendar repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;

final class CalendarRepository extends AbstractRepository {
	/**
	 * Get calendar data for the current user.
	 *
	 * @param array<string, mixed> $filters Calendar filters.
	 * @return array<string, mixed>
	 */
	public function get_for_current_user( array $filters = array() ): array {
		$view           = $this->sanitize_view( (string) ( $filters['view'] ?? 'month' ) );
		$focus          = $this->normalize_focus_date( isset( $filters['focus_date'] ) ? (string) $filters['focus_date'] : '' );
		$range          = $this->build_range( $focus, $view );
		$scope          = $this->get_scope();
		$user_id        = get_current_user_id();
		$object_type    = sanitize_key( (string) ( $filters['object_type'] ?? 'all' ) );
		$person_user_id = max( 0, (int) ( $filters['person_user_id'] ?? 0 ) );
		$project_filter = isset( $filters['project_id'] ) ? (string) $filters['project_id'] : '';
		$items          = array_merge(
			$this->get_task_items( $scope, $user_id, $range, $object_type, $person_user_id, $project_filter ),
			$this->get_project_items( $scope, $user_id, $range, $object_type, $person_user_id, $project_filter )
		);

		usort(
			$items,
			static function ( array $left, array $right ): int {
				$comparison = strcmp( (string) $left['date'], (string) $right['date'] );

				if ( 0 !== $comparison ) {
					return $comparison;
				}

				$comparison = strcmp( (string) $left['type'], (string) $right['type'] );

				if ( 0 !== $comparison ) {
					return $comparison;
				}

				return strcmp( (string) $left['title'], (string) $right['title'] );
			}
		);

		return array(
			'view'      => $view,
			'focusDate' => $focus->format( 'Y-m-d' ),
			'range'     => array(
				'start' => $range['start']->format( 'Y-m-d' ),
				'end'   => $range['end']->format( 'Y-m-d' ),
				'label' => $range['label'],
			),
			'filters'   => array(
				'object_type'    => $object_type ?: 'all',
				'person_user_id' => $person_user_id,
				'project_id'     => $project_filter,
			),
			'summary'   => $this->build_summary( $items ),
			'days'      => $this->build_days( $range, $focus, $items, 'month' === $view ),
			'items'     => $items,
		);
	}

	/**
	 * Get task-based calendar items.
	 *
	 * @param string               $scope Scope key.
	 * @param int                  $user_id Current user id.
	 * @param array<string, mixed> $range Date range.
	 * @param string               $object_type Requested object type filter.
	 * @param int                  $person_user_id Requested person filter.
	 * @param string               $project_filter Requested project filter.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_task_items( string $scope, int $user_id, array $range, string $object_type, int $person_user_id, string $project_filter ): array {
		if ( ! in_array( $object_type, array( '', 'all', 'task' ), true ) ) {
			return array();
		}

		$table  = $this->table( 'tasks' );
		list( $scope_sql, $scope_params ) = $this->get_task_scope_sql( $scope, $user_id );
		$where  = array(
			$scope_sql,
			"due_date IS NOT NULL",
			"status NOT IN ('done', 'cancelled')",
			'DATE(due_date) BETWEEN %s AND %s',
		);
		$params = array_merge(
			$scope_params,
			array(
			$range['start']->format( 'Y-m-d' ),
			$range['end']->format( 'Y-m-d' ),
			)
		);

		if ( $person_user_id > 0 ) {
			$where[]  = 'assignee_user_id = %d';
			$params[] = $person_user_id;
		}

		if ( '' !== $project_filter ) {
			if ( '0' === $project_filter ) {
				$where[] = 'project_id = 0';
			} else {
				$where[]  = 'project_id = %d';
				$params[] = max( 0, (int) $project_filter );
			}
		}

		$sql  = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY due_date ASC, priority DESC';
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $params ) );

		return array_map(
			function ( $row ): array {
				$item        = $this->row_to_array( $row );
				$project_id  = (int) ( $item['project_id'] ?? 0 );
				$assignee_id = (int) ( $item['assignee_user_id'] ?? 0 );

				return array(
					'type'         => 'task',
					'id'           => (int) ( $item['id'] ?? 0 ),
					'title'        => (string) ( $item['title'] ?? '' ),
					'label'        => $project_id > 0 ? __( 'Task due', 'coordina' ) : __( 'Standalone task due', 'coordina' ),
					'date'         => (string) ( $item['due_date'] ?? '' ),
					'status'       => (string) ( $item['status'] ?? 'new' ),
					'priority'     => (string) ( $item['priority'] ?? 'normal' ),
					'personLabel'  => $this->get_user_label( $assignee_id ),
					'projectId'    => $project_id,
					'projectLabel' => $this->get_project_label( $project_id ),
					'route'        => $project_id > 0
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
	 * Get project target-end calendar items.
	 *
	 * @param string               $scope Scope key.
	 * @param int                  $user_id Current user id.
	 * @param array<string, mixed> $range Date range.
	 * @param string               $object_type Requested object type filter.
	 * @param int                  $person_user_id Requested person filter.
	 * @param string               $project_filter Requested project filter.
	 * @return array<int, array<string, mixed>>
	 */
	private function get_project_items( string $scope, int $user_id, array $range, string $object_type, int $person_user_id, string $project_filter ): array {
		if ( ! in_array( $object_type, array( '', 'all', 'project' ), true ) ) {
			return array();
		}

		if ( '0' === $project_filter ) {
			return array();
		}

		$table  = $this->table( 'projects' );
		list( $scope_sql, $scope_params ) = $this->get_project_scope_sql( $scope, $user_id );
		$where  = array(
			$scope_sql,
			'target_end_date IS NOT NULL',
			"status NOT IN ('completed', 'cancelled', 'archived')",
			'DATE(target_end_date) BETWEEN %s AND %s',
		);
		$params = array_merge(
			$scope_params,
			array(
			$range['start']->format( 'Y-m-d' ),
			$range['end']->format( 'Y-m-d' ),
			)
		);

		if ( $person_user_id > 0 ) {
			$where[]  = 'manager_user_id = %d';
			$params[] = $person_user_id;
		}

		if ( '' !== $project_filter ) {
			$where[]  = 'id = %d';
			$params[] = max( 0, (int) $project_filter );
		}

		$sql  = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY target_end_date ASC, updated_at DESC';
		$rows = $this->wpdb->get_results( $this->wpdb->prepare( $sql, $params ) );

		return array_map(
			function ( $row ): array {
				$item       = $this->row_to_array( $row );
				$project_id = (int) ( $item['id'] ?? 0 );

				return array(
					'type'         => 'project',
					'id'           => $project_id,
					'title'        => (string) ( $item['title'] ?? '' ),
					'label'        => __( 'Project target end', 'coordina' ),
					'date'         => (string) ( $item['target_end_date'] ?? '' ),
					'status'       => (string) ( $item['status'] ?? 'planned' ),
					'priority'     => (string) ( $item['priority'] ?? 'normal' ),
					'personLabel'  => $this->get_user_label( (int) ( $item['manager_user_id'] ?? 0 ) ),
					'projectId'    => $project_id,
					'projectLabel' => (string) ( $item['title'] ?? '' ),
					'route'        => array(
						'page'        => 'coordina-projects',
						'project_id'  => $project_id,
						'project_tab' => 'overview',
					),
				);
			},
			$rows ?: array()
		);
	}

	/**
	 * Build calendar summary metrics.
	 *
	 * @param array<int, array<string, mixed>> $items Calendar items.
	 * @return array<string, int>
	 */
	private function build_summary( array $items ): array {
		$today = wp_date( 'Y-m-d' );

		return array(
			'total'    => count( $items ),
			'tasks'    => count( array_filter( $items, static function ( array $item ): bool { return 'task' === $item['type']; } ) ),
			'projects' => count( array_filter( $items, static function ( array $item ): bool { return 'project' === $item['type']; } ) ),
			'overdue'  => count(
				array_filter(
					$items,
					static function ( array $item ) use ( $today ): bool {
						return 'task' === $item['type'] && substr( (string) $item['date'], 0, 10 ) < $today;
					}
				)
			),
		);
	}

	/**
	 * Build the rendered day list.
	 *
	 * @param array<string, mixed>             $range Range details.
	 * @param DateTimeImmutable                $focus Focus date.
	 * @param array<int, array<string, mixed>> $items Calendar items.
	 * @param bool                             $month_view Whether month framing is active.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_days( array $range, DateTimeImmutable $focus, array $items, bool $month_view ): array {
		$by_day = array();

		foreach ( $items as $item ) {
			$key = substr( (string) $item['date'], 0, 10 );

			if ( ! isset( $by_day[ $key ] ) ) {
				$by_day[ $key ] = array();
			}

			$by_day[ $key ][] = $item;
		}

		$days   = array();
		$period = new DatePeriod( $range['start'], new DateInterval( 'P1D' ), $range['end']->modify( '+1 day' ) );
		$month  = $focus->format( 'm' );
		$today  = wp_date( 'Y-m-d' );

		foreach ( $period as $date ) {
			$key    = $date->format( 'Y-m-d' );
			$days[] = array(
				'date'            => $key,
				'dayNumber'       => $date->format( 'j' ),
				'weekdayLabel'    => wp_date( 'D', $date->getTimestamp() ),
				'isToday'         => $key === $today,
				'isCurrentPeriod' => ! $month_view || $date->format( 'm' ) === $month,
				'items'           => $by_day[ $key ] ?? array(),
			);
		}

		return $days;
	}

	/**
	 * Build date range for month or week view.
	 *
	 * @param DateTimeImmutable $focus Focus date.
	 * @param string            $view View key.
	 * @return array<string, mixed>
	 */
	private function build_range( DateTimeImmutable $focus, string $view ): array {
		if ( 'week' === $view ) {
			$start = $focus->modify( 'monday this week' );
			$end   = $focus->modify( 'sunday this week' );

			return array(
				'start' => $start,
				'end'   => $end,
				'label' => sprintf(
					/* translators: 1: range start date, 2: range end date */
					__( '%1$s to %2$s', 'coordina' ),
					wp_date( 'M j', $start->getTimestamp() ),
					wp_date( 'M j, Y', $end->getTimestamp() )
				),
			);
		}

		$month_start = $focus->modify( 'first day of this month' );
		$month_end   = $focus->modify( 'last day of this month' );

		return array(
			'start' => $month_start->modify( 'monday this week' ),
			'end'   => $month_end->modify( 'sunday this week' ),
			'label' => wp_date( 'F Y', $focus->getTimestamp() ),
		);
	}

	/**
	 * Normalize the chosen focus date.
	 *
	 * @param string $value Raw focus date.
	 * @return DateTimeImmutable
	 */
	private function normalize_focus_date( string $value ): DateTimeImmutable {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		if ( false === $date ) {
			return new DateTimeImmutable( 'now', wp_timezone() );
		}

		return $date;
	}

	/**
	 * Sanitize calendar view.
	 *
	 * @param string $value Requested view.
	 * @return string
	 */
	private function sanitize_view( string $value ): string {
		return in_array( $value, array( 'month', 'week' ), true ) ? $value : 'month';
	}

	/**
	 * Determine the active scope.
	 *
	 * @return string
	 */
	private function get_scope(): string {
		if ( current_user_can( 'coordina_manage_settings' ) ) {
			return 'portfolio';
		}

		if ( current_user_can( 'coordina_manage_projects' ) ) {
			return 'managed';
		}

		if ( current_user_can( 'coordina_view_dashboard' ) ) {
			return 'accessible';
		}

		return 'personal';
	}

	/**
	 * Get scoped project SQL.
	 *
	 * @param string $scope Scope key.
	 * @param int    $user_id Current user id.
	 * @return string
	 */
	private function get_project_scope_sql( string $scope, int $user_id ): array {
		if ( 'portfolio' === $scope ) {
			return array( '1=1', array() );
		}

		if ( 'accessible' === $scope ) {
			return $this->access->project_access_where( 'id' );
		}

		if ( 'managed' === $scope ) {
			return array( $this->wpdb->prepare( '(manager_user_id = %d OR created_by = %d)', $user_id, $user_id ), array() );
		}

		if ( 'personal' === $scope ) {
			return array(
				$this->wpdb->prepare(
				'id IN (SELECT DISTINCT project_id FROM ' . $this->table( 'tasks' ) . ' WHERE project_id > 0 AND (assignee_user_id = %d OR reporter_user_id = %d))',
				$user_id,
				$user_id
				),
				array()
			);
		}

		return array( '1=0', array() );
	}

	/**
	 * Get scoped task SQL.
	 *
	 * @param string $scope Scope key.
	 * @param int    $user_id Current user id.
	 * @return string
	 */
	private function get_task_scope_sql( string $scope, int $user_id ): array {
		if ( 'portfolio' === $scope ) {
			return array( '1=1', array() );
		}

		if ( 'accessible' === $scope ) {
			return $this->access->task_access_where( 'id' );
		}

		if ( 'managed' === $scope ) {
			return array(
				$this->wpdb->prepare(
				'(project_id IN (SELECT id FROM ' . $this->table( 'projects' ) . ' WHERE manager_user_id = %d OR created_by = %d) OR assignee_user_id = %d OR reporter_user_id = %d)',
				$user_id,
				$user_id,
				$user_id,
				$user_id
				),
				array()
			);
		}

		if ( 'personal' === $scope ) {
			return array( $this->wpdb->prepare( '(assignee_user_id = %d OR reporter_user_id = %d)', $user_id, $user_id ), array() );
		}

		return array( '1=0', array() );
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
