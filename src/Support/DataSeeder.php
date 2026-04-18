<?php
/**
 * Demo data seeder for realistic project scenarios.
 *
 * @package Coordina
 */

declare(strict_types=1);

namespace Coordina\Support;

use Coordina\Infrastructure\Persistence\ActivityRepository;
use Coordina\Infrastructure\Persistence\DiscussionRepository;
use Coordina\Infrastructure\Persistence\FileRepository;
use Coordina\Infrastructure\Persistence\MilestoneRepository;
use Coordina\Infrastructure\Persistence\RiskIssueRepository;
use Coordina\Platform\Contracts\AccessPolicyInterface;
use Coordina\Platform\Contracts\ApprovalRepositoryInterface;
use Coordina\Platform\Contracts\ProjectRepositoryInterface;
use Coordina\Platform\Contracts\TaskRepositoryInterface;

/**
 * Generates realistic demo projects with tasks, milestones, risks, and activity.
 */
class DataSeeder {

	/**
	 * Project repository.
	 *
	 * @var ProjectRepositoryInterface
	 */
	private $projects;

	/**
	 * Task repository.
	 *
	 * @var TaskRepositoryInterface
	 */
	private $tasks;

	/**
	 * Milestone repository.
	 *
	 * @var MilestoneRepository
	 */
	private $milestones;

	/**
	 * Risk/Issue repository.
	 *
	 * @var RiskIssueRepository
	 */
	private $risks;

	/**
	 * Approval repository.
	 *
	 * @var ApprovalRepositoryInterface
	 */
	private $approvals;

	/**
	 * Discussion repository.
	 *
	 * @var DiscussionRepository
	 */
	private $discussions;

	/**
	 * Activity repository.
	 *
	 * @var ActivityRepository
	 */
	private $activity;

	/**
	 * File repository.
	 *
	 * @var FileRepository
	 */
	private $files;

	/**
	 * Access policy.
	 *
	 * @var AccessPolicyInterface
	 */
	private $access;

	/**
	 * Constructor.
	 *
	 * @param ProjectRepositoryInterface $projects Projects repo.
	 * @param TaskRepositoryInterface $tasks Tasks repo.
	 * @param MilestoneRepository $milestones Milestones repo.
	 * @param RiskIssueRepository $risks Risks repo.
	 * @param ApprovalRepositoryInterface $approvals Approvals repo.
	 * @param DiscussionRepository $discussions Discussions repo.
	 * @param ActivityRepository $activity Activity repo.
	 * @param FileRepository $files Files repo.
	 * @param AccessPolicyInterface $access Access policy.
	 */
	public function __construct(
		ProjectRepositoryInterface $projects,
		TaskRepositoryInterface $tasks,
		MilestoneRepository $milestones,
		RiskIssueRepository $risks,
		ApprovalRepositoryInterface $approvals,
		DiscussionRepository $discussions,
		ActivityRepository $activity,
		FileRepository $files,
		AccessPolicyInterface $access
	) {
		$this->projects    = $projects;
		$this->tasks       = $tasks;
		$this->milestones  = $milestones;
		$this->risks       = $risks;
		$this->approvals   = $approvals;
		$this->discussions = $discussions;
		$this->activity    = $activity;
		$this->files       = $files;
		$this->access      = $access;
	}

	/**
	 * Seed a realistic project with all related entities.
	 *
	 * @param int $manager_user_id Project manager user ID.
	 * @return array Project data with counts.
	 */
	public function seed_website_redesign( int $manager_user_id ): array {
		// Create main project
		$project = $this->projects->create( [
			'code'              => 'WEB-2024',
			'title'             => 'Website Redesign & Migration',
			'description'       => 'Complete redesign of company website with modern UX, performance optimization, and migration to new hosting platform. Includes new design system, component library, and accessibility compliance.',
			'status'            => 'active',
			'health'            => 'on-track',
			'priority'          => 'high',
			'manager_user_id'   => $manager_user_id,
			'visibility'        => 'team',
			'start_date'        => date( 'Y-m-d', strtotime( '-2 months' ) ),
			'target_end_date'   => date( 'Y-m-d', strtotime( '+2 months' ) ),
		] );

		$project_id = (int) $project['id'];

		// Add team members
		$this->add_project_member( $project_id, $manager_user_id, 'project_manager' );
		$this->add_demo_team_members( $project_id );

		// Create milestones
		$m1 = $this->milestones->create( [
			'project_id'   => $project_id,
			'title'        => 'Design System & Component Library',
			'notes'        => 'Complete design tokens, component specs, and Figma library',
			'status'       => 'completed',
			'due_date'     => date( 'Y-m-d', strtotime( '-6 weeks' ) ),
			'owner_user_id' => $this->get_random_team_member(),
		] );

		$m2 = $this->milestones->create( [
			'project_id'   => $project_id,
			'title'        => 'Homepage & Key Landing Pages',
			'notes'        => 'Design and develop homepage, product pages, and about us',
			'status'       => 'in-progress',
			'due_date'     => date( 'Y-m-d', strtotime( '+2 weeks' ) ),
			'owner_user_id' => $this->get_random_team_member(),
		] );

		$m3 = $this->milestones->create( [
			'project_id'   => $project_id,
			'title'        => 'Internal Pages & Search',
			'notes'        => 'Blog, documentation, search functionality, and SEO optimization',
			'status'       => 'not-started',
			'due_date'     => date( 'Y-m-d', strtotime( '+4 weeks' ) ),
			'owner_user_id' => $this->get_random_team_member(),
		] );

		// Create task groups
		$design_group = $this->tasks->create_group( $project_id, [
			'title' => 'Design',
		] );
		$design_group['id'] = (int) $design_group['id'];

		$dev_group = $this->tasks->create_group( $project_id, [
			'title' => 'Development',
		] );
		$dev_group['id'] = (int) $dev_group['id'];

		$qa_group = $this->tasks->create_group( $project_id, [
			'title' => 'QA & Testing',
		] );
		$qa_group['id'] = (int) $qa_group['id'];

		// Design tasks
		$this->create_task_in_group( $project_id, $design_group['id'], [
			'title'       => 'Create color palette and typography system',
			'status'      => 'completed',
			'priority'    => 'critical',
			'due_date'    => date( 'Y-m-d', strtotime( '-4 weeks' ) ),
			'completed_at' => date( 'Y-m-d H:i:s', strtotime( '-4 weeks' ) ),
			'checklist'   => [
				'Choose primary, secondary, and accent colors',
				'Select and test 3-5 font families',
				'Create color accessibility guide',
				'Build typography scale (h1-h6, body, captions)',
			],
		] );

		$this->create_task_in_group( $project_id, $design_group['id'], [
			'title'       => 'Design component library (50+ components)',
			'status'      => 'completed',
			'priority'    => 'critical',
			'due_date'    => date( 'Y-m-d', strtotime( '-3 weeks' ) ),
			'completed_at' => date( 'Y-m-d H:i:s', strtotime( '-3 weeks' ) ),
			'checklist'   => [
				'Create button variants (primary, secondary, danger)',
				'Design form components and validation states',
				'Design navigation and menu systems',
				'Create card and container components',
				'Design modal and dialog patterns',
				'Create notification/alert components',
			],
		] );

		$this->create_task_in_group( $project_id, $design_group['id'], [
			'title'       => 'Homepage design mockup & prototyping',
			'status'      => 'in-progress',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '+1 week' ) ),
			'checklist'   => [
				'Hero section with CTA',
				'Feature highlights section',
				'Testimonials/social proof',
				'FAQ section',
				'Newsletter signup',
				'Footer navigation',
				'Responsive design (mobile, tablet, desktop)',
			],
		] );

		$this->create_task_in_group( $project_id, $design_group['id'], [
			'title'       => 'Product page design',
			'status'      => 'in-progress',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '+2 weeks' ) ),
			'checklist'   => [
				'Product showcase area',
				'Pricing table design',
				'Feature comparison',
				'Security/compliance section',
			],
		] );

		// Development tasks
		$this->create_task_in_group( $project_id, $dev_group['id'], [
			'title'       => 'Set up React component library & Storybook',
			'status'      => 'completed',
			'priority'    => 'critical',
			'due_date'    => date( 'Y-m-d', strtotime( '-3 weeks' ) ),
			'completed_at' => date( 'Y-m-d H:i:s', strtotime( '-3 weeks' ) ),
		] );

		$this->create_task_in_group( $project_id, $dev_group['id'], [
			'title'       => 'Implement design tokens in CSS/SCSS',
			'status'      => 'completed',
			'priority'    => 'critical',
			'due_date'    => date( 'Y-m-d', strtotime( '-2 weeks' ) ),
			'completed_at' => date( 'Y-m-d H:i:s', strtotime( '-2 weeks' ) ),
			'checklist'   => [
				'Colors and transparency',
				'Typography and sizing',
				'Spacing scale',
				'Shadows and elevations',
				'Animation/transition timings',
			],
		] );

		$homepage_dev = $this->create_task_in_group( $project_id, $dev_group['id'], [
			'title'       => 'Build responsive homepage (React)',
			'status'      => 'in-progress',
			'priority'    => 'critical',
			'due_date'    => date( 'Y-m-d', strtotime( '+10 days' ) ),
			'blocked'     => false,
			'checklist'   => [
				'Hero component',
				'Feature grid',
				'Testimonial carousel',
				'CTA section',
				'Mobile responsiveness',
				'Performance optimization',
			],
		] );
		$homepage_dev['id'] = (int) $homepage_dev['id'];

		$this->create_task_in_group( $project_id, $dev_group['id'], [
			'title'       => 'Implement analytics tracking',
			'status'      => 'not-started',
			'priority'    => 'medium',
			'due_date'    => date( 'Y-m-d', strtotime( '+3 weeks' ) ),
			'blocked'     => true,
			'checklist'   => [
				'GA4 setup',
				'Event tracking for key actions',
				'Conversion funnel setup',
			],
		] );

		$migration_task = $this->create_task_in_group( $project_id, $dev_group['id'], [
			'title'       => 'Database migration & content import',
			'status'      => 'not-started',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '+5 weeks' ) ),
			'blocked'     => false,
			'approval_required' => true,
			'checklist'   => [
				'Data mapping and cleansing',
				'Import scripts and testing',
				'Redirect mapping (301s)',
				'Backup strategy',
			],
		] );
		$migration_task['id'] = (int) $migration_task['id'];

		// QA Tasks
		$this->create_task_in_group( $project_id, $qa_group['id'], [
			'title'       => 'Cross-browser testing (Chrome, Firefox, Safari, Edge)',
			'status'      => 'not-started',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '+4 weeks' ) ),
		] );

		$this->create_task_in_group( $project_id, $qa_group['id'], [
			'title'       => 'Accessibility audit (WCAG 2.1 AA)',
			'status'      => 'not-started',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '+5 weeks' ) ),
			'checklist'   => [
				'Keyboard navigation',
				'Screen reader testing',
				'Color contrast review',
				'Form labeling and validation',
			],
		] );

		$performance_task = $this->create_task_in_group( $project_id, $qa_group['id'], [
			'title'       => 'Performance testing & optimization',
			'status'      => 'not-started',
			'priority'    => 'medium',
			'due_date'    => date( 'Y-m-d', strtotime( '+4 weeks' ) ),
			'blocked'     => false,
			'checklist'   => [
				'Lighthouse audit',
				'CLS, FCP, LCP optimization',
				'Image optimization',
				'Code splitting',
				'Caching strategy',
			],
		] );

		// Add risks
		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'Tight timeline for QA cycle',
			'description'    => 'Only 1 week allocated for comprehensive QA and bug fixes. May need to prioritize critical bugs.',
			'type'           => 'risk',
			'severity'       => 'high',
			'status'         => 'open',
			'mitigation_plan' => 'Implement automated testing, run parallel QA streams, prioritize defect categories',
			'owner_user_id'  => $manager_user_id,
		] );

		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'Design changes from stakeholder review',
			'description'    => 'Risk that mid-project design reviews could request significant changes, impacting timeline.',
			'type'           => 'risk',
			'severity'       => 'medium',
			'status'         => 'open',
			'mitigation_plan' => 'Lock design by end of week 1, establish change control process',
			'owner_user_id'  => $manager_user_id,
		] );

		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'Third-party integration delays',
			'description'    => 'Payment processor and email service integrations may face API delays or deprecations',
			'type'           => 'risk',
			'severity'       => 'medium',
			'status'         => 'mitigated',
			'mitigation_plan' => 'Contacted vendors week 1, receive commitments to SLA support',
			'owner_user_id'  => $manager_user_id,
		] );

		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'Security vulnerability in dependencies',
			'description'    => 'Critical npm package vulnerability discovered in React ecosystem',
			'type'           => 'issue',
			'severity'       => 'critical',
			'status'         => 'resolved',
			'mitigation_plan' => 'Updated to patched version 17.2.1 in commit 5a3e2f9c',
			'owner_user_id'  => $manager_user_id,
		] );

		// Add discussions/updates
		$this->discussions->create( [
			'object_type' => 'project',
			'object_id'   => $project_id,
			'author_user_id' => $this->get_random_team_member(),
			'message'     => 'Design system review completed. All components approved by design lead. Ready to move to dev implementation.',
		] );

		$this->discussions->create( [
			'object_type' => 'project',
			'object_id'   => $project_id,
			'author_user_id' => $this->get_random_team_member(),
			'message'     => 'Started homepage React build. Component library integration smooth. ETA for first version: 4 days.',
		] );

		$this->discussions->create( [
			'object_type' => 'task',
			'object_id'   => $homepage_dev['id'],
			'author_user_id' => $this->get_random_team_member(),
			'message'     => 'Encountered responsive design issue on mobile Safari. Testing fix with viewport media queries.',
		] );

		$this->discussions->create( [
			'object_type' => 'task',
			'object_id'   => $migration_task['id'],
			'author_user_id' => $manager_user_id,
			'message'     => 'Data mapping complete. Ready for approval. Testing redirect matrix now. All 301 redirects validated.',
		] );

		return [
			'project_id'   => $project_id,
			'project_title' => $project['title'],
			'summary'      => [
				'milestones'  => 3,
				'task_groups' => 3,
				'tasks'       => 14,
				'approvals'   => 1,
				'risks_issues' => 4,
				'discussions' => 4,
			],
		];
	}

	/**
	 * Seed a mobile app project.
	 *
	 * @param int $manager_user_id Project manager user ID.
	 * @return array Project data with counts.
	 */
	public function seed_mobile_app( int $manager_user_id ): array {
		$project = $this->projects->create( [
			'code'              => 'MOBILE-2024',
			'title'             => 'iOS/Android Mobile App Launch',
			'description'       => 'Native iOS and Android applications for on-the-go access. Includes push notifications, offline support, and biometric auth.',
			'status'            => 'active',
			'health'            => 'at-risk',
			'priority'          => 'high',
			'manager_user_id'   => $manager_user_id,
			'visibility'        => 'team',
			'start_date'        => date( 'Y-m-d', strtotime( '-6 weeks' ) ),
			'target_end_date'   => date( 'Y-m-d', strtotime( '+6 weeks' ) ),
		] );

		$project_id = (int) $project['id'];

		$this->add_project_member( $project_id, $manager_user_id, 'project_manager' );
		$this->add_demo_team_members( $project_id );

		// Milestones
		$this->milestones->create( [
			'project_id'   => $project_id,
			'title'        => 'Core Features MVP',
			'notes'        => 'Login, dashboard, core transactions',
			'status'       => 'in-progress',
			'due_date'     => date( 'Y-m-d', strtotime( '+2 weeks' ) ),
			'owner_user_id' => $this->get_random_team_member(),
		] );

		$this->milestones->create( [
			'project_id'   => $project_id,
			'title'        => 'App Store & Play Store Submission',
			'notes'        => 'Review process and approval',
			'status'       => 'not-started',
			'due_date'     => date( 'Y-m-d', strtotime( '+7 weeks' ) ),
			'owner_user_id' => $this->get_random_team_member(),
		] );

		// Risks
		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'iOS review rejection risk',
			'description'    => 'App Store review can take 24-48 hours and may require revisions',
			'type'           => 'risk',
			'severity'       => 'high',
			'status'         => 'open',
			'mitigation_plan' => 'Submit 2 weeks early, buffer for rejections',
			'owner_user_id'  => $manager_user_id,
		] );

		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'Android fragmentation issues',
			'description'    => 'Need to test across many device/OS combinations',
			'type'           => 'risk',
			'severity'       => 'medium',
			'status'         => 'open',
			'mitigation_plan' => 'Device lab with 8+ popular devices, automated testing matrix',
			'owner_user_id'  => $manager_user_id,
		] );

		return [
			'project_id'   => $project_id,
			'project_title' => $project['title'],
			'summary'      => [
				'milestones'  => 2,
				'risks_issues' => 2,
			],
		];
	}

	/**
	 * Seed a customer support process project.
	 *
	 * @param int $manager_user_id Operations manager user ID.
	 * @return array Project data with counts.
	 */
	public function seed_support_process( int $manager_user_id ): array {
		$project = $this->projects->create( [
			'code'              => 'OPS-2024',
			'title'             => 'Customer Support Process Improvement',
			'description'       => 'Operational project to standardize support ticket handling, improve response times, and introduce tiered escalation protocol.',
			'status'            => 'active',
			'health'            => 'on-track',
			'priority'          => 'medium',
			'manager_user_id'   => $manager_user_id,
			'visibility'        => 'team',
			'start_date'        => date( 'Y-m-d', strtotime( '-1 month' ) ),
			'target_end_date'   => date( 'Y-m-d', strtotime( '+1 month' ) ),
		] );

		$project_id = (int) $project['id'];

		$this->add_project_member( $project_id, $manager_user_id, 'project_manager' );
		$this->add_demo_team_members( $project_id );

		$phase1 = $this->tasks->create_group( $project_id, [
			'title' => 'Phase 1: Analysis',
		] );
		$phase1['id'] = (int) $phase1['id'];

		$this->create_task_in_group( $project_id, $phase1['id'], [
			'title'       => 'Document current support workflow',
			'status'      => 'completed',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '-3 weeks' ) ),
			'completed_at' => date( 'Y-m-d H:i:s', strtotime( '-3 weeks' ) ),
		] );

		$this->create_task_in_group( $project_id, $phase1['id'], [
			'title'       => 'Survey support team for pain points',
			'status'      => 'completed',
			'priority'    => 'high',
			'due_date'    => date( 'Y-m-d', strtotime( '-2 weeks' ) ),
			'completed_at' => date( 'Y-m-d H:i:s', strtotime( '-2 weeks' ) ),
		] );

		$this->risks->create( [
			'project_id'     => $project_id,
			'title'          => 'Team resistance to new processes',
			'description'    => 'Support team familiar with current workflow, may resist changes',
			'type'           => 'risk',
			'severity'       => 'medium',
			'status'         => 'open',
			'mitigation_plan' => 'Involve team in design, provide training and champions',
			'owner_user_id'  => $manager_user_id,
		] );

		return [
			'project_id'   => $project_id,
			'project_title' => $project['title'],
			'summary'      => [
				'tasks'       => 2,
				'risks_issues' => 1,
			],
		];
	}

	/**
	 * Create a task within a task group.
	 *
	 * @param int $project_id Project ID.
	 * @param int $group_id Task group ID.
	 * @param array $data Task data.
	 * @return array Created task.
	 */
	private function create_task_in_group( int $project_id, int $group_id, array $data ): array {
		$task = $this->tasks->create(
			array_merge(
				[
					'project_id'      => $project_id,
					'task_group_id'   => $group_id,
					'assignee_user_id' => $this->get_random_team_member(),
					'reporter_user_id' => $this->get_random_team_member(),
				],
				$data
			)
		);

		return $task;
	}

	/**
	 * Add a project member.
	 *
	 * @param int $project_id Project ID.
	 * @param int $user_id User ID.
	 * @param string $role Member role.
	 */
	private function add_project_member( int $project_id, int $user_id, string $role ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'coordina_project_members',
			[
				'project_id' => $project_id,
				'user_id'    => $user_id,
				'member_role' => $role,
				'created_at' => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s', '%s' ]
		);
	}

	/**
	 * Add demo team members to a project.
	 *
	 * @param int $project_id Project ID.
	 */
	private function add_demo_team_members( int $project_id ): void {
		$users = get_users( [ 'number' => 10 ] );
		foreach ( $users as $user ) {
			if ( $user->ID !== get_current_user_id() ) {
				$this->add_project_member( $project_id, $user->ID, 'team_member' );
				if ( count( $users ) > 5 ) {
					break;
				}
			}
		}
	}

	/**
	 * Get a random team member user ID.
	 *
	 * @return int Random user ID.
	 */
	private function get_random_team_member(): int {
		$users = get_users( [ 'number' => 20 ] );
		if ( empty( $users ) ) {
			return get_current_user_id();
		}
		return $users[ array_rand( $users ) ]->ID;
	}
}
