<?php
/**
 * WP-CLI commands for Coordina demo data.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Support;

use WP_CLI;
use RuntimeException;

/**
 * Coordina admin commands.
 */
class DataSeedCommand {
	/**
	 * Seeder factory callback.
	 *
	 * @var callable|null
	 */
	private static $seeder_factory;

	/**
	 * Register WP-CLI commands.
	 *
	 * @param callable|null $seeder_factory Optional seeder factory.
	 */
	public static function register( ?callable $seeder_factory = null ): void {
		self::$seeder_factory = $seeder_factory;

		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command(
			'coordina seed',
			[ static::class, 'seed_demo_projects' ],
			[
				'shortdesc' => 'Seed realistic demo projects with tasks, milestones, risks, and more',
				'synopsis'  => [
					[
						'type'        => 'assoc',
						'name'        => 'type',
						'description' => 'Type of project to seed: all, website, mobile, support (default: all)',
						'optional'    => true,
						'default'     => 'all',
					],
					[
						'type'        => 'flag',
						'name'        => 'manager_id',
						'description' => 'User ID of project manager (default: current user)',
						'optional'    => true,
					],
				],
			]
		);

		WP_CLI::add_command(
			'coordina seed-clear',
			[ static::class, 'clear_demo_projects' ],
			[
				'shortdesc' => 'Clear all demo projects (use with caution)',
				'synopsis'  => [],
			]
		);
	}

	/**
	 * Seed demo projects.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associated arguments.
	 */
	public static function seed_demo_projects( array $args, array $assoc_args ): void {
		// Get manager ID
		$manager_id = isset( $assoc_args['manager_id'] ) 
			? (int) $assoc_args['manager_id'] 
			: get_current_user_id();

		if ( ! get_userdata( $manager_id ) ) {
			WP_CLI::error( "User ID {$manager_id} does not exist." );
		}

		// Initialize seeder
		$seeder = self::get_seeder();

		$type     = $assoc_args['type'] ?? 'all';
		$projects = [];

		try {
			switch ( $type ) {
				case 'website':
					$projects[] = $seeder->seed_website_redesign( $manager_id );
					WP_CLI::success( 'Website redesign project created.' );
					break;

				case 'mobile':
					$projects[] = $seeder->seed_mobile_app( $manager_id );
					WP_CLI::success( 'Mobile app project created.' );
					break;

				case 'support':
					$projects[] = $seeder->seed_support_process( $manager_id );
					WP_CLI::success( 'Support process project created.' );
					break;

				case 'all':
					$projects[] = $seeder->seed_website_redesign( $manager_id );
					$projects[] = $seeder->seed_mobile_app( $manager_id );
					$projects[] = $seeder->seed_support_process( $manager_id );
					WP_CLI::success( 'All demo projects created.' );
					break;

				default:
					WP_CLI::error( "Unknown project type: {$type}. Use 'all', 'website', 'mobile', or 'support'." );
			}

			// Display summary
			foreach ( $projects as $project ) {
				WP_CLI::log(
					sprintf(
						"\n📋 %s (ID: %d)\n   %s\n   Tasks: %d | Milestones: %d | Risks/Issues: %d",
						$project['project_title'],
						$project['project_id'],
						str_repeat( '─', 60 ),
						$project['summary']['tasks'] ?? 0,
						$project['summary']['milestones'] ?? 0,
						$project['summary']['risks_issues'] ?? 0
					)
				);
			}
		} catch ( \Exception $e ) {
			WP_CLI::error( 'Seeding failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Clear demo projects.
	 *
	 * @param array $args Positional arguments.
	 * @param array $assoc_args Associated arguments.
	 */
	public static function clear_demo_projects( array $args, array $assoc_args ): void {
		global $wpdb;

		WP_CLI::confirm( 'Are you sure you want to delete all projects and related data? This cannot be undone.' );

		try {
			// Get all projects
			$projects = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}coordina_projects" );

			foreach ( (array) $projects as $project_id ) {
				// Delete related records
				$wpdb->delete(
					$wpdb->prefix . 'coordina_activities',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_approvals',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_risks_issues',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_milestones',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				$task_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}coordina_tasks WHERE project_id = %d",
						$project_id
					)
				);

				foreach ( (array) $task_ids as $task_id ) {
					$wpdb->delete(
						$wpdb->prefix . 'coordina_task_checklist_items',
						[ 'task_id' => $task_id ],
						[ '%d' ]
					);
				}

				$wpdb->delete(
					$wpdb->prefix . 'coordina_tasks',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_task_groups',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				$wpdb->delete(
					$wpdb->prefix . 'coordina_project_members',
					[ 'project_id' => $project_id ],
					[ '%d' ]
				);

				// Delete project
				$wpdb->delete(
					$wpdb->prefix . 'coordina_projects',
					[ 'id' => $project_id ],
					[ '%d' ]
				);
			}

			WP_CLI::success( 'All projects cleared.' );
		} catch ( \Exception $e ) {
			WP_CLI::error( 'Clear failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Get seeder instance.
	 *
	 * @return DataSeeder
	 */
	private static function get_seeder(): DataSeeder {
		if ( is_callable( self::$seeder_factory ) ) {
			$seeder = call_user_func( self::$seeder_factory );

			if ( $seeder instanceof DataSeeder ) {
				return $seeder;
			}
		}

		throw new RuntimeException( 'Coordina demo seeder service is not available.' );
	}
}
