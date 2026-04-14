<?php
/**
 * Activity feed repository.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Infrastructure\Persistence;

final class ActivityRepository extends AbstractRepository {
	/**
	 * Fetch access-aware activity items.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public function get_items( array $args ): array {
		$page        = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page    = max( 1, min( 50, (int) ( $args['per_page'] ?? $this->default_activity_per_page() ) ) );
		$object_type = isset( $args['object_type'] ) ? sanitize_key( (string) $args['object_type'] ) : '';
		$object_id   = isset( $args['object_id'] ) ? max( 0, (int) $args['object_id'] ) : 0;
		$event_type  = isset( $args['event_type'] ) ? sanitize_key( (string) $args['event_type'] ) : '';
		$actor_id    = isset( $args['actor_user_id'] ) ? max( 0, (int) $args['actor_user_id'] ) : 0;
		$project_id  = isset( $args['project_id'] ) ? max( 0, (int) $args['project_id'] ) : 0;
		$candidates  = $this->get_candidate_rows( $object_type, $object_id, $event_type, $actor_id, $project_id );
		$items       = array_values( array_filter( array_map( array( $this, 'map_item' ), $candidates ) ) );
		$total       = count( $items );
		$offset      = ( $page - 1 ) * $per_page;

		return array(
			'items'      => array_slice( $items, $offset, $per_page ),
			'total'      => $total,
			'page'       => $page,
			'per_page'   => $per_page,
			'totalPages' => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Get project-specific activity.
	 *
	 * @param int                  $project_id Project id.
	 * @param array<string, mixed> $args Extra query args.
	 * @return array<string, mixed>
	 */
	public function get_for_project( int $project_id, array $args = array() ): array {
		$args['project_id'] = $project_id;

		if ( ! isset( $args['per_page'] ) ) {
			$args['per_page'] = $this->default_activity_per_page();
		}

		return $this->get_items( $args );
	}

	/**
	 * Get project activity summary.
	 *
	 * @param int $project_id Project id.
	 * @return array<string, mixed>
	 */
	public function get_project_summary( int $project_id ): array {
		$items  = $this->get_for_project( $project_id, array( 'page' => 1, 'per_page' => 120 ) );
		$latest = $items['items'][0]['createdAt'] ?? '';

		return array(
			'total'    => (int) ( $items['total'] ?? 0 ),
			'latestAt' => (string) $latest,
			'charts'   => $this->build_project_activity_charts( $items['items'] ?? array() ),
		);
	}

	/**
	 * Resolve the default activity page size.
	 */
	private function default_activity_per_page(): int {
		$settings = ( new SettingsRepository() )->get();

		return max( 5, min( 50, (int) ( $settings['general']['activity_page_size'] ?? 10 ) ) );
	}

	/**
	 * Fetch broad candidates before applying object-level access.
	 *
	 * @param string $object_type Object type filter.
	 * @param int    $object_id Object id filter.
	 * @param string $event_type Event type filter.
	 * @param int    $actor_id Actor user filter.
	 * @param int    $project_id Project filter.
	 * @return array<int, object>
	 */
	private function get_candidate_rows( string $object_type, int $object_id, string $event_type, int $actor_id, int $project_id ): array {
		$table  = $this->table( 'activity_log' );
		$where  = array( '1=1' );
		$params = array();

		if ( '' !== $object_type ) {
			$where[]  = 'object_type = %s';
			$params[] = $object_type;
		}

		if ( $object_id > 0 ) {
			$where[]  = 'object_id = %d';
			$params[] = $object_id;
		}

		if ( '' !== $event_type ) {
			$where[]  = 'event_type = %s';
			$params[] = $event_type;
		}

		if ( $actor_id > 0 ) {
			$where[]  = 'actor_user_id = %d';
			$params[] = $actor_id;
		}

		if ( $project_id > 0 ) {
			$where[] = $this->project_activity_where();
			array_push( $params, $project_id, $project_id, $project_id, $project_id, $project_id, $project_id, $project_id, $project_id );
		}

		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT 250';

		if ( empty( $params ) ) {
			return $this->wpdb->get_results( $sql ) ?: array();
		}

		return $this->wpdb->get_results( $this->wpdb->prepare( $sql, $params ) ) ?: array();
	}

	/**
	 * Build project activity membership SQL.
	 */
	private function project_activity_where(): string {
		$tasks_table      = $this->table( 'tasks' );
		$risks_table      = $this->table( 'risks_issues' );
		$milestones_table = $this->table( 'milestones' );
		$approvals_table  = $this->table( 'approvals' );

		return "((object_type = 'project' AND object_id = %d)
			OR (object_type = 'task' AND object_id IN (SELECT id FROM {$tasks_table} WHERE project_id = %d))
			OR (object_type IN ('risk', 'issue') AND object_id IN (SELECT id FROM {$risks_table} WHERE project_id = %d))
			OR (object_type = 'milestone' AND object_id IN (SELECT id FROM {$milestones_table} WHERE project_id = %d))
			OR (object_type = 'approval' AND object_id IN (
				SELECT id FROM {$approvals_table} WHERE (object_type = 'project' AND object_id = %d)
				OR (object_type = 'task' AND object_id IN (SELECT id FROM {$tasks_table} WHERE project_id = %d))
				OR (object_type IN ('risk', 'issue') AND object_id IN (SELECT id FROM {$risks_table} WHERE project_id = %d))
				OR (object_type = 'milestone' AND object_id IN (SELECT id FROM {$milestones_table} WHERE project_id = %d))
			)))";
	}

	/**
	 * Map a row if the current user may see it.
	 *
	 * @param object|null $row Activity row.
	 * @return array<string, mixed>
	 */
	private function map_item( $row ): array {
		$item = $this->row_to_array( $row );

		if ( empty( $item ) || ! $this->can_view_activity( $item ) ) {
			return array();
		}

		$object_type = sanitize_key( (string) ( $item['object_type'] ?? '' ) );
		$object_id   = (int) ( $item['object_id'] ?? 0 );
		$project_id  = $this->resolve_project_id_for_activity( $object_type, $object_id );

		return array(
			'id'           => (int) $item['id'],
			'objectType'   => $object_type,
			'objectTypeLabel' => $this->activity_object_type_label( $object_type ),
			'objectId'     => $object_id,
			'objectLabel'  => $this->activity_object_label( $object_type, $object_id ),
			'projectId'    => $project_id,
			'projectLabel' => $this->get_project_label( $project_id ),
			'eventType'    => (string) ( $item['event_type'] ?? '' ),
			'actorLabel'   => $this->get_user_label( (int) ( $item['actor_user_id'] ?? 0 ) ),
			'message'      => ! empty( $item['message'] ) ? (string) $item['message'] : __( 'Activity captured for this object.', 'coordina' ),
			'createdAt'    => (string) ( $item['created_at'] ?? '' ),
			'route'        => $this->activity_route( $object_type, $object_id, $project_id ),
		);
	}

	/**
	 * Check item-level activity visibility.
	 *
	 * @param array<string, mixed> $item Activity row.
	 * @return bool
	 */
	private function can_view_activity( array $item ): bool {
		if ( (int) ( $item['actor_user_id'] ?? 0 ) === get_current_user_id() ) {
			return true;
		}

		$object_type = sanitize_key( (string) ( $item['object_type'] ?? '' ) );
		$object_id   = (int) ( $item['object_id'] ?? 0 );

		if ( 'milestone' === $object_type ) {
			return $this->access->can_view_milestone( $object_id );
		}

		return $this->access->can_view_context( $object_type, $object_id );
	}

	/**
	 * Resolve project id for supported activity objects.
	 */
	private function resolve_project_id_for_activity( string $object_type, int $object_id ): int {
		if ( 'milestone' === $object_type ) {
			return (int) $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT project_id FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', $object_id ) );
		}

		return $this->resolve_project_id_for_context( $object_type, $object_id );
	}

	/**
	 * Resolve object label.
	 */
	private function activity_object_label( string $object_type, int $object_id ): string {
		if ( 'milestone' === $object_type ) {
			$title = $this->wpdb->get_var( $this->wpdb->prepare( 'SELECT title FROM ' . $this->table( 'milestones' ) . ' WHERE id = %d', $object_id ) );
			return $title ? (string) $title : __( 'Milestone', 'coordina' );
		}

		return $this->resolve_context_label( $object_type, $object_id );
	}

	/**
	 * Resolve a friendly object-type label.
	 */
	private function activity_object_type_label( string $object_type ): string {
		switch ( $object_type ) {
			case 'project':
				return __( 'Project', 'coordina' );
			case 'task':
				return __( 'Task', 'coordina' );
			case 'milestone':
				return __( 'Milestone', 'coordina' );
			case 'risk':
				return __( 'Risk', 'coordina' );
			case 'issue':
				return __( 'Issue', 'coordina' );
			case 'approval':
				return __( 'Approval', 'coordina' );
			case 'request':
				return __( 'Request', 'coordina' );
			default:
				return __( 'Activity item', 'coordina' );
		}
	}

	/**
	 * Build compact project activity chart series.
	 *
	 * @param array<int, array<string, mixed>> $items Activity items.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private function build_project_activity_charts( array $items ): array {
		$actor_counts    = array();
		$category_counts = array();
		$timestamps      = array();

		foreach ( $items as $item ) {
			$created_at = (string) ( $item['createdAt'] ?? '' );
			$timestamp  = strtotime( $created_at );
			if ( false !== $timestamp ) {
				$timestamps[] = $timestamp;
			}

			$actor_label = (string) ( $item['actorLabel'] ?? __( 'System', 'coordina' ) );
			if ( '' === $actor_label ) {
				$actor_label = __( 'System', 'coordina' );
			}
			$actor_counts[ $actor_label ] = ( $actor_counts[ $actor_label ] ?? 0 ) + 1;

			$category_label = (string) ( $item['objectTypeLabel'] ?? $this->activity_object_type_label( (string) ( $item['objectType'] ?? '' ) ) );
			$category_counts[ $category_label ] = ( $category_counts[ $category_label ] ?? 0 ) + 1;
		}

		arsort( $actor_counts );
		arsort( $category_counts );

		return array(
			'rhythm'     => $this->build_rhythm_chart( $timestamps ),
			'actors'     => $this->slice_chart_counts( $actor_counts, 5 ),
			'categories' => $this->slice_chart_counts( $category_counts, 5 ),
		);
	}

	/**
	 * Build an adaptive rhythm chart using day, week, or month buckets.
	 *
	 * @param int[] $timestamps Activity timestamps.
	 * @return array<string, mixed>
	 */
	private function build_rhythm_chart( array $timestamps ): array {
		if ( empty( $timestamps ) ) {
			return array(
				'title'       => __( 'Daily rhythm', 'coordina' ),
				'granularity' => 'day',
				'buckets'     => array(),
			);
		}

		sort( $timestamps );
		$oldest    = (int) reset( $timestamps );
		$latest    = (int) end( $timestamps );
		$span_days = max( 1, (int) ceil( ( $latest - $oldest ) / DAY_IN_SECONDS ) + 1 );

		if ( $span_days <= 21 ) {
			return array(
				'title'       => __( 'Daily rhythm', 'coordina' ),
				'granularity' => 'day',
				'buckets'     => $this->build_day_buckets( $timestamps, $latest, min( 7, $span_days ) ),
			);
		}

		if ( $span_days <= 120 ) {
			return array(
				'title'       => __( 'Weekly rhythm', 'coordina' ),
				'granularity' => 'week',
				'buckets'     => $this->build_week_buckets( $timestamps, $latest, 8 ),
			);
		}

		return array(
			'title'       => __( 'Monthly rhythm', 'coordina' ),
			'granularity' => 'month',
			'buckets'     => $this->build_month_buckets( $timestamps, $latest, 6 ),
		);
	}

	/**
	 * Build daily chart buckets.
	 *
	 * @param int[] $timestamps Activity timestamps.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_day_buckets( array $timestamps, int $latest, int $periods ): array {
		$counts = array();
		foreach ( $timestamps as $timestamp ) {
			$key = wp_date( 'Y-m-d', $timestamp );
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
		}

		$rows = array();
		for ( $offset = $periods - 1; $offset >= 0; $offset-- ) {
			$timestamp = strtotime( '-' . $offset . ' days', $latest );
			$key       = wp_date( 'Y-m-d', $timestamp );
			$rows[]    = array(
				'key'   => $key,
				'label' => wp_date( 'M j', $timestamp ),
				'count' => (int) ( $counts[ $key ] ?? 0 ),
			);
		}

		return $rows;
	}

	/**
	 * Build weekly chart buckets.
	 *
	 * @param int[] $timestamps Activity timestamps.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_week_buckets( array $timestamps, int $latest, int $periods ): array {
		$counts      = array();
		$latest_week = strtotime( 'monday this week', $latest );

		foreach ( $timestamps as $timestamp ) {
			$week_start = strtotime( 'monday this week', $timestamp );
			$key        = wp_date( 'Y-m-d', $week_start );
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
		}

		$rows = array();
		for ( $offset = $periods - 1; $offset >= 0; $offset-- ) {
			$timestamp = strtotime( '-' . $offset . ' weeks', $latest_week );
			$key       = wp_date( 'Y-m-d', $timestamp );
			$rows[]    = array(
				'key'   => $key,
				'label' => wp_date( 'M j', $timestamp ),
				'count' => (int) ( $counts[ $key ] ?? 0 ),
			);
		}

		return $rows;
	}

	/**
	 * Build monthly chart buckets.
	 *
	 * @param int[] $timestamps Activity timestamps.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_month_buckets( array $timestamps, int $latest, int $periods ): array {
		$counts       = array();
		$latest_month = strtotime( gmdate( 'Y-m-01 00:00:00', $latest ) );

		foreach ( $timestamps as $timestamp ) {
			$month_start = strtotime( gmdate( 'Y-m-01 00:00:00', $timestamp ) );
			$key         = wp_date( 'Y-m', $month_start );
			$counts[ $key ] = ( $counts[ $key ] ?? 0 ) + 1;
		}

		$rows = array();
		for ( $offset = $periods - 1; $offset >= 0; $offset-- ) {
			$timestamp = strtotime( '-' . $offset . ' months', $latest_month );
			$key       = wp_date( 'Y-m', $timestamp );
			$rows[]    = array(
				'key'   => $key,
				'label' => wp_date( 'M Y', $timestamp ),
				'count' => (int) ( $counts[ $key ] ?? 0 ),
			);
		}

		return $rows;
	}

	/**
	 * Normalize an activity date to a day key.
	 */
	private function normalize_activity_date_key( string $value ): string {
		$text = trim( $value );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $text, $matches ) ) {
			return $matches[0];
		}

		$timestamp = strtotime( $text );
		if ( false === $timestamp ) {
			return '';
		}

		return wp_date( 'Y-m-d', $timestamp );
	}

	/**
	 * Convert grouped counts into compact chart rows.
	 *
	 * @param array<string, int> $counts Grouped counts.
	 * @param int                $limit Result limit.
	 * @return array<int, array<string, mixed>>
	 */
	private function slice_chart_counts( array $counts, int $limit ): array {
		$rows = array();

		foreach ( array_slice( $counts, 0, $limit, true ) as $label => $count ) {
			$rows[] = array(
				'label' => (string) $label,
				'count' => (int) $count,
			);
		}

		return $rows;
	}

	/**
	 * Build a UI route for the activity object.
	 */
	private function activity_route( string $object_type, int $object_id, int $project_id ): array {
		if ( 'project' === $object_type ) {
			return array( 'page' => 'coordina-projects', 'project_id' => $object_id, 'project_tab' => 'overview' );
		}

		if ( 'milestone' === $object_type ) {
			return array(
				'page'         => 'coordina-milestone',
				'milestone_id' => $object_id,
				'project_id'   => $project_id,
				'project_tab'  => $project_id > 0 ? 'milestones' : '',
			);
		}

		if ( 'task' === $object_type ) {
			return array(
				'page'       => 'coordina-task',
				'task_id'    => $object_id,
				'project_id' => $project_id,
				'project_tab'=> $project_id > 0 ? 'work' : '',
			);
		}

		if ( 'request' === $object_type ) {
			return array( 'page' => 'coordina-requests' );
		}

		if ( in_array( $object_type, array( 'risk', 'issue' ), true ) ) {
			return array(
				'page'          => 'coordina-risk-issue',
				'risk_issue_id' => $object_id,
				'project_id'    => $project_id,
				'project_tab'   => $project_id > 0 ? 'risks-issues' : '',
			);
		}

		if ( 'approval' === $object_type ) {
			return array( 'page' => $project_id > 0 ? 'coordina-projects' : 'coordina-approvals', 'project_id' => $project_id, 'project_tab' => $project_id > 0 ? 'approvals' : '' );
		}

		return array( 'page' => 'coordina-dashboard' );
	}
}
