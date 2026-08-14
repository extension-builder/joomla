<?php
/**
 * @package    Joomla.Component.Builder.Tests
 *
 * @created    14th August, 2026
 * @author     Llewellyn van der Merwe <https://dev.vdm.io>
 * @git        Joomla Component Builder <https://git.vdm.dev/joomla/Component-Builder>
 * @copyright  Copyright (C) 2015 Vast Development Method. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace VDM\Joomla\Gitea\Tests\Service;


use Joomla\DI\ServiceProviderInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use VDM\Joomla\Gitea\Abstraction\Api;
use VDM\Joomla\Gitea\Service\Admin;
use VDM\Joomla\Gitea\Service\Issue;
use VDM\Joomla\Gitea\Service\Miscellaneous;
use VDM\Joomla\Gitea\Service\Notifications;
use VDM\Joomla\Gitea\Service\Organization;
use VDM\Joomla\Gitea\Service\Package;
use VDM\Joomla\Gitea\Service\Repository;
use VDM\Joomla\Gitea\Service\Settings;
use VDM\Joomla\Gitea\Service\User;
use VDM\Joomla\Gitea\Tests\Support\ServiceProviderTestCase;
use VDM\Joomla\Gitea\Utilities\Uri;


/**
 * Exhaustive endpoint service-provider wiring contracts.
 *
 * @since  1.0.0
 */
#[CoversClass(Admin::class)]
#[CoversClass(Issue::class)]
#[CoversClass(Miscellaneous::class)]
#[CoversClass(Notifications::class)]
#[CoversClass(Organization::class)]
#[CoversClass(Package::class)]
#[CoversClass(Repository::class)]
#[CoversClass(Settings::class)]
#[CoversClass(User::class)]
#[UsesClass(Api::class)]
#[UsesClass(Uri::class)]
final class EndpointProvidersTest extends ServiceProviderTestCase
{
	/**
	 * Verify every declared working endpoint alias, shared instance, and dependency.
	 *
	 * @param   ServiceProviderInterface    $provider  Provider under test.
	 * @param   array<class-string, string>  $services  Expected services.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('providerContracts')]
	public function testEndpointProviderContract(
		ServiceProviderInterface $provider,
		array $services
	): void
	{
		$this->assertEndpointProvider($provider, $services);
	}

	/**
	 * Provide every endpoint class-to-key mapping declared by the service layer.
	 *
	 * Issue Deadline is intentionally absent because its provider references a
	 * nonexistent getDeadline() factory method; that production defect is reported
	 * separately and must not be normalized into a passing contract.
	 *
	 * @return  iterable<string, array{ServiceProviderInterface, array<class-string, string>}>
	 * @since   1.0.0
	 */
	public static function providerContracts(): iterable
	{
		yield 'admin' => [new Admin(), [
			\VDM\Joomla\Gitea\Admin\Cron::class => 'Gitea.Admin.Cron',
			\VDM\Joomla\Gitea\Admin\Organizations::class => 'Gitea.Admin.Organizations',
			\VDM\Joomla\Gitea\Admin\Unadopted::class => 'Gitea.Admin.Unadopted',
			\VDM\Joomla\Gitea\Admin\Users::class => 'Gitea.Admin.Users',
			\VDM\Joomla\Gitea\Admin\Users\Keys::class => 'Gitea.Admin.Users.Keys',
			\VDM\Joomla\Gitea\Admin\Users\Organization::class => 'Gitea.Admin.Users.Organization',
			\VDM\Joomla\Gitea\Admin\Users\Repository::class => 'Gitea.Admin.Users.Repository'
		]];

		yield 'issue' => [new Issue(), [
			\VDM\Joomla\Gitea\Issue::class => 'Gitea.Issue',
			\VDM\Joomla\Gitea\Issue\Comments::class => 'Gitea.Issue.Comments',
			\VDM\Joomla\Gitea\Issue\Repository\Comments::class => 'Gitea.Issue.Repository.Comments',
			\VDM\Joomla\Gitea\Labels::class => 'Gitea.Labels',
			\VDM\Joomla\Gitea\Issue\Labels::class => 'Gitea.Issue.Labels',
			\VDM\Joomla\Gitea\Issue\Milestones::class => 'Gitea.Issue.Milestones',
			\VDM\Joomla\Gitea\Issue\Reactions::class => 'Gitea.Issue.Reactions',
			\VDM\Joomla\Gitea\Issue\Reactions\Comment::class => 'Gitea.Issue.Reactions.Comment',
			\VDM\Joomla\Gitea\Issue\Stopwatch::class => 'Gitea.Issue.Stopwatch',
			\VDM\Joomla\Gitea\Issue\Subscriptions::class => 'Gitea.Issue.Subscriptions',
			\VDM\Joomla\Gitea\Issue\Timeline::class => 'Gitea.Issue.Timeline',
			\VDM\Joomla\Gitea\Issue\Times::class => 'Gitea.Issue.Times'
		]];

		yield 'miscellaneous' => [new Miscellaneous(), [
			\VDM\Joomla\Gitea\Miscellaneous\Activitypub::class => 'Gitea.Miscellaneous.Activitypub',
			\VDM\Joomla\Gitea\Miscellaneous\Gpg::class => 'Gitea.Miscellaneous.Gpg',
			\VDM\Joomla\Gitea\Miscellaneous\Markdown::class => 'Gitea.Miscellaneous.Markdown',
			\VDM\Joomla\Gitea\Miscellaneous\NodeInfo::class => 'Gitea.Miscellaneous.NodeInfo',
			\VDM\Joomla\Gitea\Miscellaneous\Version::class => 'Gitea.Miscellaneous.Version'
		]];

		yield 'notifications' => [new Notifications(), [
			\VDM\Joomla\Gitea\Notifications::class => 'Gitea.Notifications',
			\VDM\Joomla\Gitea\Notifications\Repository::class => 'Gitea.Notifications.Repository',
			\VDM\Joomla\Gitea\Notifications\Thread::class => 'Gitea.Notifications.Thread'
		]];

		yield 'organization' => [new Organization(), [
			\VDM\Joomla\Gitea\Organization::class => 'Gitea.Organization',
			\VDM\Joomla\Gitea\Organization\Hooks::class => 'Gitea.Organization.Hooks',
			\VDM\Joomla\Gitea\Organization\Labels::class => 'Gitea.Organization.Labels',
			\VDM\Joomla\Gitea\Organization\Members::class => 'Gitea.Organization.Members',
			\VDM\Joomla\Gitea\Organization\PublicMembers::class => 'Gitea.Organization.Public.Members',
			\VDM\Joomla\Gitea\Organization\Repository::class => 'Gitea.Organization.Repository',
			\VDM\Joomla\Gitea\Organization\Teams::class => 'Gitea.Organization.Teams',
			\VDM\Joomla\Gitea\Organization\Teams\Members::class => 'Gitea.Organization.Teams.Members',
			\VDM\Joomla\Gitea\Organization\Teams\Repository::class => 'Gitea.Organization.Teams.Repository',
			\VDM\Joomla\Gitea\Organization\User::class => 'Gitea.Organization.User'
		]];

		yield 'package' => [new Package(), [
			\VDM\Joomla\Gitea\Package::class => 'Gitea.Package',
			\VDM\Joomla\Gitea\Package\Files::class => 'Gitea.Package.Files',
			\VDM\Joomla\Gitea\Package\Owner::class => 'Gitea.Package.Owner'
		]];

		yield 'repository' => [new Repository(), [
			\VDM\Joomla\Gitea\Repository::class => 'Gitea.Repository',
			\VDM\Joomla\Gitea\Repository\Archive::class => 'Gitea.Repository.Archive',
			\VDM\Joomla\Gitea\Repository\Assignees::class => 'Gitea.Repository.Assignees',
			\VDM\Joomla\Gitea\Repository\Attachments::class => 'Gitea.Repository.Attachments',
			\VDM\Joomla\Gitea\Repository\Branch::class => 'Gitea.Repository.Branch',
			\VDM\Joomla\Gitea\Repository\Branch\Protection::class => 'Gitea.Repository.Branch.Protection',
			\VDM\Joomla\Gitea\Repository\Collaborator::class => 'Gitea.Repository.Collaborator',
			\VDM\Joomla\Gitea\Repository\Commits::class => 'Gitea.Repository.Commits',
			\VDM\Joomla\Gitea\Repository\Contents::class => 'Gitea.Repository.Contents',
			\VDM\Joomla\Gitea\Repository\Forks::class => 'Gitea.Repository.Forks',
			\VDM\Joomla\Gitea\Repository\Gpg::class => 'Gitea.Repository.Gpg',
			\VDM\Joomla\Gitea\Repository\Hooks::class => 'Gitea.Repository.Hooks',
			\VDM\Joomla\Gitea\Repository\Hooks\Git::class => 'Gitea.Repository.Hooks.Git',
			\VDM\Joomla\Gitea\Repository\Keys::class => 'Gitea.Repository.Keys',
			\VDM\Joomla\Gitea\Repository\Languages::class => 'Gitea.Repository.Languages',
			\VDM\Joomla\Gitea\Repository\Media::class => 'Gitea.Repository.Media',
			\VDM\Joomla\Gitea\Repository\Merge::class => 'Gitea.Repository.Merge',
			\VDM\Joomla\Gitea\Repository\Mirror::class => 'Gitea.Repository.Mirror',
			\VDM\Joomla\Gitea\Repository\Notes::class => 'Gitea.Repository.Notes',
			\VDM\Joomla\Gitea\Repository\Patch::class => 'Gitea.Repository.Patch',
			\VDM\Joomla\Gitea\Repository\Pulls::class => 'Gitea.Repository.Pulls',
			\VDM\Joomla\Gitea\Repository\Refs::class => 'Gitea.Repository.Refs',
			\VDM\Joomla\Gitea\Repository\Releases::class => 'Gitea.Repository.Releases',
			\VDM\Joomla\Gitea\Repository\Remote::class => 'Gitea.Repository.Remote',
			\VDM\Joomla\Gitea\Repository\Reviewers::class => 'Gitea.Repository.Reviewers',
			\VDM\Joomla\Gitea\Repository\Reviews::class => 'Gitea.Repository.Reviews',
			\VDM\Joomla\Gitea\Repository\Stargazers::class => 'Gitea.Repository.Stargazers',
			\VDM\Joomla\Gitea\Repository\Statuses::class => 'Gitea.Repository.Statuses',
			\VDM\Joomla\Gitea\Repository\Tags::class => 'Gitea.Repository.Tags',
			\VDM\Joomla\Gitea\Repository\Teams::class => 'Gitea.Repository.Teams',
			\VDM\Joomla\Gitea\Repository\Templates::class => 'Gitea.Repository.Templates',
			\VDM\Joomla\Gitea\Repository\Times::class => 'Gitea.Repository.Times',
			\VDM\Joomla\Gitea\Repository\Topics::class => 'Gitea.Repository.Topics',
			\VDM\Joomla\Gitea\Repository\Transfer::class => 'Gitea.Repository.Transfer',
			\VDM\Joomla\Gitea\Repository\Trees::class => 'Gitea.Repository.Trees',
			\VDM\Joomla\Gitea\Repository\Watchers::class => 'Gitea.Repository.Watchers',
			\VDM\Joomla\Gitea\Repository\Wiki::class => 'Gitea.Repository.Wiki'
		]];

		yield 'settings' => [new Settings(), [
			\VDM\Joomla\Gitea\Settings\Api::class => 'Gitea.Settings.Api',
			\VDM\Joomla\Gitea\Settings\Attachment::class => 'Gitea.Settings.Attachment',
			\VDM\Joomla\Gitea\Settings\Repository::class => 'Gitea.Settings.Repository',
			\VDM\Joomla\Gitea\Settings\Ui::class => 'Gitea.Settings.Ui'
		]];

		yield 'user' => [new User(), [
			\VDM\Joomla\Gitea\User::class => 'Gitea.User',
			\VDM\Joomla\Gitea\User\Applications::class => 'Gitea.User.Applications',
			\VDM\Joomla\Gitea\User\Emails::class => 'Gitea.User.Emails',
			\VDM\Joomla\Gitea\User\Followers::class => 'Gitea.User.Followers',
			\VDM\Joomla\Gitea\User\Following::class => 'Gitea.User.Following',
			\VDM\Joomla\Gitea\User\Gpg::class => 'Gitea.User.Gpg',
			\VDM\Joomla\Gitea\User\Keys::class => 'Gitea.User.Keys',
			\VDM\Joomla\Gitea\User\Repos::class => 'Gitea.User.Repos',
			\VDM\Joomla\Gitea\User\Settings::class => 'Gitea.User.Settings',
			\VDM\Joomla\Gitea\User\Starred::class => 'Gitea.User.Starred',
			\VDM\Joomla\Gitea\User\Subscriptions::class => 'Gitea.User.Subscriptions',
			\VDM\Joomla\Gitea\User\Teams::class => 'Gitea.User.Teams',
			\VDM\Joomla\Gitea\User\Times::class => 'Gitea.User.Times',
			\VDM\Joomla\Gitea\User\Tokens::class => 'Gitea.User.Tokens'
		]];
	}
}
