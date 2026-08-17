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

/**
 * Explicit ownership for production subjects with real tests.
 *
 * Remove a subject from coverage-baseline.php in the same change that adds an
 * entry here. The owner is relative to this test-suite directory and must be an
 * existing PHPUnit *Test.php file.
 *
 * @return  array<string, array{mode: string, owner: string}>
 * @since   1.0.0
 */
return [
	'VDM.Joomla.Git/src/Repository/Contents.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Git/src/Repository/ContentsTest.php'
	],
	'VDM.Joomla.Gitea/src/Abstraction/Api.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Gitea/src/Abstraction/ApiTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Cron.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Organizations.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Unadopted.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Users.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Users/Keys.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Users/Organization.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Admin/Users/Repository.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/FactoryTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Comments.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Deadline.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Labels.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Milestones.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Reactions.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Reactions/Comment.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Repository/Comments.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Stopwatch.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Subscriptions.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Timeline.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Issue/Times.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Labels.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Miscellaneous/Activitypub.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Miscellaneous/Gpg.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Miscellaneous/Markdown.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Miscellaneous/NodeInfo.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Miscellaneous/Version.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Notifications.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Notifications/Repository.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/AdministrativeEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Notifications/Thread.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Hooks.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Labels.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Members.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/PublicMembers.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Repository.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Teams.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Teams/Members.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/Teams/Repository.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Organization/User.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Package.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Package/Files.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Package/Owner.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Archive.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Assignees.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Attachments.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Branch.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Branch/Protection.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Collaborator.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Commits.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Contents.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Forks.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Gpg.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Hooks.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Hooks/Git.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Keys.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Languages.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Media.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Merge.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Mirror.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Mirrors.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Notes.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Patch.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Pulls.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Refs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Releases.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Remote.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Reviewers.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Reviews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Stargazers.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Statuses.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Tags.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Teams.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Templates.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Times.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Topics.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Transfer.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Trees.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Watchers.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Repository/Wiki.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/RepositoryEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Admin.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Issue.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Jcb.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/JcbTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Miscellaneous.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Notifications.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Organization.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Package.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Repository.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Settings.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/User.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/EndpointProvidersTest.php'
	],
	'VDM.Joomla.Gitea/src/Service/Utilities.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Gitea/src/Service/UtilitiesTest.php'
	],
	'VDM.Joomla.Gitea/src/Settings/Api.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Settings/Attachment.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Settings/Repository.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Settings/Ui.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Applications.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Emails.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Followers.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Following.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Gpg.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Keys.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Repos.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Settings.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Starred.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Subscriptions.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Teams.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Times.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/OrganizationPackageUserEndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/User/Tokens.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla.Gitea/src/Contract/EndpointRequestContractsTest.php'
	],
	'VDM.Joomla.Gitea/src/Utilities/Http.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Gitea/src/Utilities/HttpTest.php'
	],
	'VDM.Joomla.Gitea/src/Utilities/Http/Transport/UnsafeCurl.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Gitea/src/Utilities/Http/Transport/UnsafeCurlTest.php'
	],
	'VDM.Joomla.Gitea/src/Utilities/Response.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Gitea/src/Utilities/ResponseTest.php'
	],
	'VDM.Joomla.Gitea/src/Utilities/Uri.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Gitea/src/Utilities/UriTest.php'
	],
	'VDM.Joomla.Github/src/Abstraction/Api.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Abstraction/ApiTest.php'
	],
	'VDM.Joomla.Github/src/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Github/src/FactoryTest.php'
	],
	'VDM.Joomla.Github/src/Repository/Contents.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Repository/ContentsTest.php'
	],
	'VDM.Joomla.Github/src/Repository/Tags.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Repository/TagsTest.php'
	],
	'VDM.Joomla.Github/src/Repository/Wiki.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Repository/WikiTest.php'
	],
	'VDM.Joomla.Github/src/Service/Utilities.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Github/src/Service/UtilitiesTest.php'
	],
	'VDM.Joomla.Github/src/Utilities/Http.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Utilities/HttpTest.php'
	],
	'VDM.Joomla.Github/src/Utilities/Response.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Utilities/ResponseTest.php'
	],
	'VDM.Joomla.Github/src/Utilities/Uri.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Github/src/Utilities/UriTest.php'
	],
	'VDM.Joomla.Openai/src/Abstraction/Api.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/Abstraction/ApiTest.php'
	],
	'VDM.Joomla.Openai/src/Audio.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/AudioTest.php'
	],
	'VDM.Joomla.Openai/src/Chat.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/ChatTest.php'
	],
	'VDM.Joomla.Openai/src/Completions.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/CompletionsTest.php'
	],
	'VDM.Joomla.Openai/src/Edits.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/EditsTest.php'
	],
	'VDM.Joomla.Openai/src/Embeddings.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/EmbeddingsTest.php'
	],
	'VDM.Joomla.Openai/src/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Openai/src/FactoryTest.php'
	],
	'VDM.Joomla.Openai/src/Files.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/FilesTest.php'
	],
	'VDM.Joomla.Openai/src/FineTunes.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/FineTunesTest.php'
	],
	'VDM.Joomla.Openai/src/Images.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/ImagesTest.php'
	],
	'VDM.Joomla.Openai/src/Models.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/ModelsTest.php'
	],
	'VDM.Joomla.Openai/src/Moderate.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/ModerateTest.php'
	],
	'VDM.Joomla.Openai/src/Service/Api.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Openai/src/Service/ApiTest.php'
	],
	'VDM.Joomla.Openai/src/Service/Utilities.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla.Openai/src/Service/UtilitiesTest.php'
	],
	'VDM.Joomla.Openai/src/Utilities/Http.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/Utilities/HttpTest.php'
	],
	'VDM.Joomla.Openai/src/Utilities/Response.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/Utilities/ResponseTest.php'
	],
	'VDM.Joomla.Openai/src/Utilities/Uri.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla.Openai/src/Utilities/UriTest.php'
	],
	'VDM.Joomla/src/Abstraction/ActiveRegistry.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/ActiveRegistryTest.php'
	],
	'VDM.Joomla/src/Abstraction/BaseTable.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/BaseTableTest.php'
	],
	'VDM.Joomla/src/Abstraction/Console.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/ConsoleTest.php'
	],
	'VDM.Joomla/src/Abstraction/Database.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/DatabaseTest.php'
	],
	'VDM.Joomla/src/Abstraction/Factory.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Abstraction/FactoryTest.php'
	],
	'VDM.Joomla/src/Abstraction/FunctionRegistry.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/FunctionRegistryTest.php'
	],
	'VDM.Joomla/src/Abstraction/Grep.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/GrepTest.php'
	],
	'VDM.Joomla/src/Abstraction/Model.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/ModelTest.php'
	],
	'VDM.Joomla/src/Abstraction/PHPConfigurationChecker.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/PHPConfigurationCheckerTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/RegistryTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/GetString.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/GetStringTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/InArray.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/InArrayTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/IsArray.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/IsArrayTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/IsString.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/IsStringTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/PathCount.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/PathCountTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/PathToString.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/PathToStringTest.php'
	],
	'VDM.Joomla/src/Abstraction/Registry/Traits/VarExport.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Registry/Traits/VarExportTest.php'
	],
	'VDM.Joomla/src/Abstraction/Remote/Base.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Remote/BaseTest.php'
	],
	'VDM.Joomla/src/Abstraction/Remote/Config.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Remote/ConfigTest.php'
	],
	'VDM.Joomla/src/Abstraction/Remote/Get.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Remote/GetTest.php'
	],
	'VDM.Joomla/src/Abstraction/Remote/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/Remote/SetTest.php'
	],
	'VDM.Joomla/src/Abstraction/Schema.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/SchemaTest.php'
	],
	'VDM.Joomla/src/Abstraction/SchemaChecker.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Abstraction/SchemaCheckerTest.php'
	],
	'VDM.Joomla/src/Abstraction/Versioning.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Abstraction/VersioningTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/Api.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Api/NetworkTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/BaseRegistry.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Abstraction/BaseRegistryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/ComponentConfig.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Abstraction/ComponentConfigTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/Console/Import.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/ItemImportTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/Console/Package.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/Console/Package/Get.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Abstraction/Console/Package/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Api/Network.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Api/NetworkTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Adminview/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Adminview/AdminviewContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Adminview/DefaultOrdering.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Adminview/AdminviewContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Adminview/Permission.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Adminview/AdminviewContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Alias/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Alias/DataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/EditBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedEditBodyRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminView/EditBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedEditBodyRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminView/EditBodyInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedEditBodyRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SecondRunAdmin.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/FootableScripts.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedFootableScriptsRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminView/FootableScripts.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedFootableScriptsRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminView/FootableScriptsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedFootableScriptsRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LinkedView/ListBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/LinkedView/ListBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/LinkedView/ListBodyInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ListItemBuilderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ListLinkInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/SelectionTranslation.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelSelectionTranslationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/FieldRelation.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelFieldRelationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ItemsStringFix.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedItemsStringFixTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/ItemsStringFix.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedItemsStringFixTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/ItemsStringFixInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedItemsStringFixTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/SelectionTranslationMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelSelectionTranslationMethodTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/CustomQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelCustomQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/CustomFieldTypeFileInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelCustomQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LinkedView/ListQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/LinkedView/ListQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/LinkedView/ListQueryInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewListQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LinkedView/ListHead.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LinkedViewListHeadTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Dashboard/Icons.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/DashboardIconsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ItemSave.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelItemSaveTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/ItemSave.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelItemSaveTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/ItemSaveInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelItemSaveTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/AliasTitleFix.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelAliasTitleFixTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/BatchCopy.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelBatchTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/BatchCopy.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelBatchTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/BatchCopyInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelBatchTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/BatchMove.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelBatchTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/BatchMove.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelBatchTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/BatchMoveInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelBatchTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/GetForm.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelGetFormTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/GetForm.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelGetFormTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/GetFormInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelGetFormTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ItemsMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelItemsMethodTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/ItemsMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelItemsMethodTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/ItemsMethodInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelItemsMethodTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/EximportView.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/ListQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelListQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/ListQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelListQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/ListQueryInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModelListQueryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/SearchQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelQueryClauseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/FilterQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ModelQueryClauseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Field/CustomFieldCode.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/FilterFieldSupportTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/FilterFieldHelper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedFilterFieldHelperTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminViews/FilterFieldHelper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedFilterFieldHelperTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/FilterFieldHelperInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedFilterFieldHelperTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/FilterFieldFile.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/FilterFieldSupportTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedAdminViewsListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminViews/ListBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedAdminViewsListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ListBodyInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedAdminViewsListBodyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LinkedView/Builder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewBuilderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/LinkedView/Builder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewBuilderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/LinkedView/Builder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewBuilderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/LinkedView/BuilderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedLinkedViewBuilderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/CustomTabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/EditTabsRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/TabLayoutFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/EditTabsRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminView/FadeInEffect.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LayoutAndFadeRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/DisplayMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedDisplayMethodRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListHead.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedListHeadRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListLink.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListLinkTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListItem.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListItem/ItemCode.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListItem/Link.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListItem/LinkAuthority.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListItem/LinkLogic.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListItemBuilder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ToolbarComposer.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ComHelperClass/CryptKey.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SecurityRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ComHelperClass/ExcelMethods.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedExcelHelperRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ComHelperClass/UikitMethods.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/ComHelperClass/UikitMethodsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ViewBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedViewBodyRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Component/ImageType.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Component/LicenseLock.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SecurityRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Component/Whmcs.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SecurityRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/CustomView/DisplayMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedDisplayMethodRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/CustomButtons.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/DynamicButtons.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/SharedArchitectureRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/AdminView/AddModalToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/AdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/AdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/ComHelperClass/CreateUser.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Controller/AllowAdd.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Controller/AllowEdit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Controller/AllowEditViews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/CustomAdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/CustomAdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Dashboard/View.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Model/CanDelete.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Model/CanEditState.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Model/CheckInNow.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Module/Dispatcher.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Module/Helper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Module/Library.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Module/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Module/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Module/Template.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Plugin/Extension.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Plugin/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/Plugin/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFive/SiteView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/AdminView/AddModalToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/AdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/AdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/ComHelperClass/CreateUser.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Controller/AllowAdd.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Controller/AllowEdit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Controller/AllowEditViews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/CustomAdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/CustomAdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Dashboard/View.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/CustomView/DisplayMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedDisplayMethodRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Model/CanDelete.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Model/CanEditState.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Model/CheckInNow.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Module/Dispatcher.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Module/Helper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Module/Library.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Module/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Module/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Module/Template.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Plugin/Extension.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Plugin/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/Plugin/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaFour/SiteView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/AdminView/AddModalToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/AdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/AdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/ComHelperClass/CreateUser.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Controller/AllowAdd.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Controller/AllowEdit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Controller/AllowEditViews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/CustomAdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/CustomAdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Dashboard/View.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Model/CanDelete.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Model/CanEditState.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Model/CheckInNow.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Module/Dispatcher.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Module/Helper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Module/Library.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Module/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Module/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Module/Template.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Plugin/Extension.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Plugin/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/Plugin/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaSix/SiteView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminView/AddModalToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminViews/DisplayMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedDisplayMethodRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminViews/ListHead.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedListHeadRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/AdminViews/ViewBody.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedViewBodyRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/ComHelperClass/CreateUser.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/ComHelperClass/ExcelMethods.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedExcelHelperRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Controller/AllowAdd.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Controller/AllowEdit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Controller/AllowEditViews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/CustomAdminView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/CustomAdminViews/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Dashboard/View.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/CustomView/DisplayMethod.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedDisplayMethodRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Menu/CustomView.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedMenuRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/AllowEdit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/CanDelete.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/CanEditState.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Model/CheckInNow.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Module/Dispatcher.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Module/Helper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Module/Library.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Module/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Module/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Module/Template.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Plugin/Extension.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Plugin/MainXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/Plugin/Provider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedModulePluginRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/JoomlaThree/SiteView/AddToolBar.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedToolbarDashboardRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Layout/View.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/LayoutAndFadeRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Menu/AdminView.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedMenuRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Menu/CustomView.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedMenuRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/Model/AllowEdit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedPermissionRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/AccessSwitch.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/AccessSwitchList.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/AdminFilterType.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Alias.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/AssetsRules.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BaseSixFour.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Category.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CategoryCode.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CategoryOtherName.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CheckBox.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ComponentFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ConfigFieldsets.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ConfigFieldsetsCustomfield.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ContentMulti.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ContentOne.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Contributors.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomAdminAdded.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListLinkTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomAdminViewListId.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListLinkTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomAdminViewListLink.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/AdminViews/ListLinkTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomAlias.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomFieldLinks.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomForm.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomList.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/CustomTabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DatabaseKeys.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DatabaseTables.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DatabaseUninstall.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DatabaseUniqueGuid.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DatabaseUniqueKeys.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DoNotEscape.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DynamicButtons.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/DynamicFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/EventDispatcher.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ExtensionCustomFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ExtensionsParams.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/FieldGroupControl.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/FieldNames.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/FieldRelations.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Filter.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/FootableScripts.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/FrontendParams.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/GetAsLookup.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/GetModule.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/GoogleChart.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/HasMenuGlobal.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/HasPermissions.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/HiddenFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/History.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/IntegerFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ItemsMethodEximportString.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ItemsMethodListString.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/JsonItem.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/JsonItemArray.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/JsonString.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/LanguageMessages.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Languages.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Layout.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/LayoutData.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/LibraryManager.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ListColumnNumber.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Architecture/VersionedListHeadRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ListFieldClass.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ListHeadOverride.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ListJoin.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Lists.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/MainTextField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/MetaData.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ModelBasicField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ModelExpertField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ModelExpertFieldInitiator.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ModelMediumField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ModelWhmcsField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/MovedPublishingFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Multilingual.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/MysqlTableSetting.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/NewPublishingFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OnlyFunctionButtons.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OrderZero.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OtherFilter.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OtherGroup.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OtherJoin.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OtherOrder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OtherQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/OtherWhere.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionAction.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionComponent.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionCore.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionDashboard.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionGlobalAction.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/PermissionViews.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Request.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderPolicyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Router.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ScriptMediaSwitch.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ScriptUserSwitch.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Search.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SelectionTranslation.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteDecrypt.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteDynamicGet.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteEditView.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteFieldData.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteFieldDecodeFilter.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/SiteMainGet.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Sort.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/TabCounter.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Tags.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/TemplateData.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/Title.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/UikitComp.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/UpdateMysql.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Builder/ViewsDefaultOrdering.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Builder/BuilderRegistryContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/ComponentTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/Dashboard.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/ComponentMetadataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/Data.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/ComponentMetadataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/JoomlaFive/Settings.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/SettingsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/JoomlaFour/Settings.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/SettingsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/JoomlaSix/Settings.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/SettingsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/JoomlaThree/Settings.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/SettingsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/Placeholder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/ComponentMetadataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/StructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/Structuremultiple.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/StructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Component/Structuresingle.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Component/StructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Config.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/AccessSections.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/AccessSectionsCategory.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/AccessSectionsJoomlaFields.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/Builders.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsets.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsCustomfield.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsEmailHelper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsEncryption.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsGlobal.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsGooglechart.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsGroupControl.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsSiteControl.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetsUikit.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/ConfigFieldsetLeafTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CustomButtonPermissions.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CustomFieldTypeFile.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/EmailHelper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldAsString.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldDynamic.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldString.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldsetDynamic.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldsetExtension.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldCreatorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldsetString.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/FieldsetXML.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/Helper.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/Layout.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/Permission.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/Request.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/Router.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RouterConstructorDefault.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RouterConstructorManual.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RouterMethodsDefault.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RouterMethodsManual.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/RoutingAndAccessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Creator/SiteFieldData.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Creator/CreatorStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CustomcodeTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/Dispenser.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/DispenserTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/External.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/ExternalAndGuiTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/Extractor.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/ExtractorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/Extractor/Paths.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/Extractor/PathsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/Gui.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/ExternalAndGuiTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/Hash.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/HashAndLockBaseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/LockBase.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customcode/HashAndLockBaseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Customview/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Customview/DataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/CustomGetMethods.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/ItemOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/CustomJoin.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryCompositionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/Data.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/DataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/DecodeColumn.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldonContentPrepare.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FilterColumn.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/GetItem.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/ItemOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/GetItems.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/ItemOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/Globals.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/JoinStructure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/ListQuery.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryCompositionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/Methods.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryCompositionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/Queries.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryCompositionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryFilter.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryClauseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryGroup.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryClauseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryOrder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryClauseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryWhere.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/QueryClauseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/Selection.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/SelectionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/UikitLoader.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Dynamicget/FieldRendererTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FileContent.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FileContentTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/Files/Dynamic.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FilesUpdaterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/Files/Module.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FilesUpdaterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/Files/Plugin.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FilesUpdaterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/Files/Power.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FilesUpdaterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/Files/StaticFiles.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FilesUpdaterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/Files/Updater.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/FilesUpdaterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaFive/InstallScript.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/InstallScriptTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaFive/MoveFieldsRules.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/MoveFieldsRulesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaFour/InstallScript.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/InstallScriptTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaFour/MoveFieldsRules.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/MoveFieldsRulesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaSix/InstallScript.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/InstallScriptTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaSix/MoveFieldsRules.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/MoveFieldsRulesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaThree/InstallScript.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/InstallScriptTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/JoomlaThree/MoveFieldsRules.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/MoveFieldsRulesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Extension/VersionUpdate.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Extension/VersionUpdateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/FactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/FieldTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/Attributes.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/AttributesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/Customcode.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/Data.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/DataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/DatabaseName.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/Groups.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaFive/CoreField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaFive/CoreRule.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaFive/InputButton.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/InputButtonTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaFour/CoreField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaFour/CoreRule.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaFour/InputButton.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/InputButtonTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaSix/CoreField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaSix/CoreRule.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaSix/InputButton.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/InputButtonTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaThree/CoreField.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaThree/CoreRule.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/CoreCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/JoomlaThree/InputButton.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/InputButtonTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/ModalSelect.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/ModalSelectTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/Name.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/Rule.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/TypeName.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Field/UniqueName.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Field/FieldBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/FilePaths.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Initializer.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminView/AddModalToolBarInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminView/AddToolBarInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/AddToolBarInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/DisplayMethodInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ListHeadInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/AdminViews/ViewBodyInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/ComHelperClass/CreateUserInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/ComHelperClass/ExcelMethodsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Controller/AllowAddInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Controller/AllowEditInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Controller/AllowEditViewsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/CustomAdmin/AddToolBarInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/CustomView/DisplayMethodInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Dashboard/ViewInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Menu/CustomViewInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/AllowEditInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/CanDeleteInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/CanEditStateInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Model/CheckInNowInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/DispatcherInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/HelperInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/LibraryInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/ProviderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Module/TemplateInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Plugin/ExtensionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/Plugin/ProviderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Architecture/SiteView/AddToolBarInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Component/PlaceholderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Component/SettingsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/Fielddynamicinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/Fieldsetinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Creator/Fieldtypeinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/DispenserInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/ExternalInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/ExtractorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/GuiInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Customcode/LockBaseInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/CustomcodeInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/EventInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Extension/InstallInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/ExtensionFilesUpdateInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Field/CoreFieldInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Field/CoreRuleInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Field/InputButtonInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/GetScriptInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/HeaderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/HistoryInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Language/ExtractorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/LanguageInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Model/CustomtabsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/ModuleDataInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/MoveFieldsRulesInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/PlaceholderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/PluginDataInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Power/ExtractorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/Power/InjectorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/PowerInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomla/Path.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaFive/Event.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaFive/Header.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaFive/History.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaFour/Event.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaFour/Header.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaFour/History.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaPower.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaPower/Extractor.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaPower/Injector.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaSix/Event.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaSix/Header.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaSix/History.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaThree/Event.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaThree/Header.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/JoomlaThree/History.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/VersionedIntegrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaFive/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaFive/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaFive/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaFour/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaFour/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaFour/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaSix/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaSix/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaSix/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaThree/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaThree/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/JoomlaThree/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlamodule/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaFive/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaFive/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaFive/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaFour/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaFour/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaFour/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaSix/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaSix/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaSix/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaThree/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedDataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaThree/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedInfusionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/JoomlaThree/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Joomlaplugin/VersionedStructureTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/LanguageTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Extractor.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/ExtractorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Fieldset.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/FieldsetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Insert.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/PersistenceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Multilingual.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/PersistenceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Purge.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/PersistenceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/SetAndTranslationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Translation.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/SetAndTranslationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Language/Update.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Language/PersistenceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Library/Data.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Library/LibraryPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Library/Document.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Library/LibraryPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Library/IncludeHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Library/LibraryPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Library/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Library/LibraryPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Adminviews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ViewModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Ajaxadmin.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/AjaxModelsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Ajaxcustomview.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/AjaxModelsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Conditions.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/FieldDefinitionModelsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Createdate.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ModelNormalizationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Cssadminview.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/CustomCodeModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Csscustomview.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/CustomCodeModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Customadminviews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ViewModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Customalias.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/AssociationModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Custombuttons.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/AjaxModelsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Customimportscripts.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/PhpModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Dynamicget.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/DynamicgetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Fields.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/FieldDefinitionModelsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Filesfolders.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ModelNormalizationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Historyadminview.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/HistoryadminviewTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Historycomponent.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/HistorycomponentTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Javascriptadminview.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/CustomCodeModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Javascriptcustomview.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/CustomCodeModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/JoomlaFive/Customtabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/VersionedCustomtabsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/JoomlaFour/Customtabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/VersionedCustomtabsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/JoomlaSix/Customtabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/VersionedCustomtabsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/JoomlaThree/Customtabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/VersionedCustomtabsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Joomlamodules.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/AssociationModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Joomlaplugins.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/AssociationModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Libraries.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/LibrariesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Linkedviews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/RegistryModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Loader.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/LoaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Modifieddate.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ModelNormalizationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Mysqlsettings.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ViewModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Permissions.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ModelNormalizationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Phpadminview.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/PhpModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Phpcustomview.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/PhpModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Relations.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/RelationsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Router.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/RouterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Siteviews.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ViewModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Sql.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/SqlTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Sqldump.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/SqldumpTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Sqltweaking.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/RegistryModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Tabs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ModelNormalizationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Updateserver.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/UpdateserverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Updatesql.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/RegistryModelTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Model/Whmcs.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Model/ModelNormalizationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Placeholder.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Placeholder/Reverse.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Power.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Power/Autoloader.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Power/Extractor.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Power/Infusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Power/Injector.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Power/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Power/PowerPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Registry.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Adminview.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureComponent.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureController.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureDashboard.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureModel.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureModule.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitecturePlugin.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/ArchitectureView.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/BuilderAJ.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/BuilderLZ.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Compiler.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Component.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Creator.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Customcode.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Customview.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Event.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Extension.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Field.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Header.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/History.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/JoomlaPower.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Joomlamodule.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Joomlaplugin.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Language.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Library.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Model.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Package.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Placeholder.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Power.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Templatelayout.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Service/Utilities.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Templatelayout/Data.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Templatelayout/DataTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/ComplexityEngine.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UtilitiesPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Counter.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UtilitiesPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Dynamicpath.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/DynamicpathTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/FieldHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/FieldHelperTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/File.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/FileTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/FileInjector.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UtilitiesPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Files.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/CoreStateTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Folder.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/FolderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Indent.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/IndentTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Line.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/LineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Minify.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/MinifyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Pathfix.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/PathfixTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Paths.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UtilitiesPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Placefix.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/PlacefixTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Structure.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UtilitiesPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Unique.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UniqueTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Valuation.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/UtilitiesPipelineTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/Xml.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Compiler/Utilities/XmlTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/Compiler.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/CompilerTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/ItemImport.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/ItemImportTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/Package/Get.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/Package/Init.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/Package/Pull.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/Package/Push.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Console/Package/Reset.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Console/Package/PackageConsoleTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/CryptTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt/Aes.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Crypt/AesTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt/Aes/Legacy.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Crypt/Aes/LegacyTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt/FOF.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Crypt/FOFTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt/KeyLoader.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Crypt/KeyLoaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt/Password.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Crypt/PasswordTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Crypt/Random.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Crypt/RandomTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Data/Migrator/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Factory/DomainFactoryCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Data/Migrator/Guid.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Data/Migrator/GuidTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Abstraction/Layout.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/LayoutContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Abstraction/Locator.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Abstraction/Writer.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Collector.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Locator/Form.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Locator/Language.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Locator/Schema.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Locator/Table.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Locator/View.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Manifest.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Scanner.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/Selector.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Discovery/DiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Extruder.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtruderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Helper/Extrusion.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Interfaces/ExtruderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Interfaces/LayoutInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Interfaces/LocatorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Interfaces/PrecedenceInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Interfaces/ReaderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Interfaces/WriterInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Layout/Heuristic.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/LayoutContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Layout/JoomlaFive.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/LayoutContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Layout/JoomlaFour.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/LayoutContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Layout/JoomlaSix.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/LayoutContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Layout/JoomlaThree.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/LayoutContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Dispatcher.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Form.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Language.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Php/Literal.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Php/MethodMap.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/PhpMethodTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Php/Methods.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/PhpMethodTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Schema.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Sql/CreateTable.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Sql/Insert.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Sql/Splitter.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/Table.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/View/Layout.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/View/SiteView.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/View/Split.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/View/Template.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Reader/ReaderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Form.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Inventory.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Language.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Message.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Report.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Resolved.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Schema.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Scope.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Source.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/Table.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Registry/View.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionStateContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Assembler.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/AssemblerTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Condition.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/FieldXml.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Fieldtype.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/FieldtypeTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Guid.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Language.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Precedence.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/PrecedenceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Relation.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Role.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Tab.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Prefix.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/Text.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ViewName.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Resolver/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Service/Discovery.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Service/Extrusion.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Service/Reader.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Service/Registry.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Service/Resolver.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Service/Writer.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/ExtrusionServiceProviderTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/AdminCustomTabs.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/AdminFields.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/AdminFieldsConditions.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/AdminView.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/Component.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/ComponentAdminViews.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/ComponentSiteViews.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/Dispatcher.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/Field.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/Layout.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/SiteView.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/Template.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Extrusion/Writer/WriterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Factory.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/FactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/FactoryTrait.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/FactoryTraitTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Fieldtype/FieldtypeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Factory/DomainFactoryCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Grep.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Fieldtype/FieldtypeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Readme/Item.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Fieldtype/FieldtypeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Readme/Main.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Fieldtype/FieldtypeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Fieldtype/FieldtypeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Remote/Set.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Fieldtype/FieldtypeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Fieldtype/Service/Fieldtype.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/DomainProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Definition.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/DefinitionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Display.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/FileBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Factory/DomainFactoryCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Handler.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/FileBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Image.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/FileBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Manager.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/FileBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Service/File.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/DomainProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/Type.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/FileBehaviorTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/File/TypeDefinition.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/File/TypeDefinitionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Assessor.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/ImportMappingTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Factory/DomainFactoryCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Item.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/ImportMappingTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Item/Persistent.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/ImportProcessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Item/Transient.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/ImportProcessTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Persistent/Assessor.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/Persistent/PersistentMessageTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Persistent/Message.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/Persistent/PersistentMessageTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Import/Status.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Import/ImportMappingTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Architecture/Module/MainXMLInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Architecture/Plugin/MainXMLInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Cryptinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/File/DefinitionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/File/TypeDefinitionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Module/InfusionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Module/StructureInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Plugin/InfusionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Plugin/StructureInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Interfaces/Serverinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RepositoryFactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Grep.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Readme/Item.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Readme/Main.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Remote/Set.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/SpecializedSetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/JoomlaPower/Service/JoomlaPower.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/DomainProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Markdown/Html.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Markdown/HtmlTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Network/Core.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Network/RepositoryDiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Network/ParsedUrls.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Network/RepositoryDiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Network/Resolve.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Network/RepositoryDiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Network/Status.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Network/RepositoryDiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Network/Url.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Network/RepositoryDiscoveryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/PHPConfigurationChecker.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/PHPConfigurationCheckerTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminCustomTabs/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminFields/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminFieldsConditions/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminFieldsRelations/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminView/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminView/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/AdminView/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Builder/Get.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Builder/GetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Builder/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Builder/SetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Children/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Children/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ClassExtends/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ClassMethod/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ClassProperty/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Component/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Component/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Component/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentAdminViews/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentConfig/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentCustomAdminMenus/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentCustomAdminViews/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentDashboard/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentFilesFolders/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentModules/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentPlaceholders/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentPlugins/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentRouter/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentSiteViews/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ComponentUpdates/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Config.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/FactoryConfigTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/CustomAdminView/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/CustomAdminView/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/CustomAdminView/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/CustomCode/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/CustomCode/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/CustomCode/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Dependency/Resolver.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Dependency/ResolverTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Dependency/Tracker.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/StateRegistryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/DynamicGet/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/DynamicGet/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/DynamicGet/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/FactoryConfigTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Field/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Field/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Field/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/File/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Folder/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Grep.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/GrepTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/GrepContent.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/GrepTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaModule/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaModule/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaModule/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaModuleFilesFoldersUrls/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaModuleUpdates/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaPlugin/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaPlugin/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaPlugin/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaPluginFilesFoldersUrls/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaPluginGroup/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/JoomlaPluginUpdates/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Layout/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Layout/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Layout/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Library/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Library/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Library/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/LibraryConfig/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/LibraryFilesFoldersUrls/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/MessageBus.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/StateRegistryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Placeholder/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/Alias/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/SpecializedSetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/CustomCode/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/SpecializedSetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/DynamicGet/Set.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/SpecializedSetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/GetContent.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ContentTransferTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/GetFile.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ContentTransferTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/GetFolder.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ContentTransferTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/SetContent.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ContentTransferTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/SetFile.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ContentTransferTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Remote/SetFolder.php' => [
		'mode' => 'integration',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ContentTransferTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/AdminViewGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/AdminViewSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/ComponentGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/ComponentSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/CustomAdminViewGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/CustomAdminViewSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/CustomCodeGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/CustomCodeSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/DependenciesGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/DependenciesSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/DynamicGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/DynamicSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/FieldGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/FieldSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/JoomlaModuleGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/JoomlaModuleSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/JoomlaPluginGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/JoomlaPluginSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/LayoutGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/LayoutSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/LibraryGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/LibrarySet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/Package.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/Power.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/SiteViewGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/SiteViewSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/TemplateGet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Service/TemplateSet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/SiteView/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/SiteView/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/SiteView/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Template/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Template/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/Template/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ValidationRule/Readme/Item.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ValidationRule/Readme/Main.php' => [
		'mode' => 'characterization',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Readme/ReadmeCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Package/ValidationRule/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Package/Remote/ConfigCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RepositoryFactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator/Bucket.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator/ClassInjector.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator/ClassInjectorBuilder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator/Search.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator/ServiceProvider.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Generator/ServiceProviderBuilder.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Grep.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Interfaces/TableInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Parser.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Plantuml.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/GenerationContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Readme/Item.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Readme/Main.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Remote/Set.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/SpecializedSetTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Service/Generator.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Service/Git.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Service/Gitea.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Service/Github.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Service/Power.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Power/Table.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Remote/Get.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Remote/Grep.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Remote/Set.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Remote/SetDependenciesTrait.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Remote/Version.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RepositoryFactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Grep.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Readme/Item.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Readme/Main.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Repository/Service/Repository.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/DomainProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Abstraction/Engine.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Agent.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Agent/Find.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Agent/Replace.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Agent/Search.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Agent/Update.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Database/Insert.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Database/Load.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Engine/Basic.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Engine/Regex.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RepositoryFactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Interfaces/FindInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Interfaces/InsertInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Interfaces/LoadInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Interfaces/ReplaceInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Interfaces/SearchInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Interfaces/SearchTypeInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchEngineAndAgentsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Model/Insert.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Model/Load.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/SearchBoundaryAndOrchestrationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Service/Agent.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Service/Database.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Service/Model.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Search/Service/Search.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Search/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Server.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Server/ServerContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Server/Ftp.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Server/ServerContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Server/Load.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Server/ServerContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Server/Model/Load.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Server/ServerContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Server/Sftp.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Server/ServerContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Api.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/CoreRules.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Crypt.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Data.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Gitea.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Import.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Network.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Server.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Spreadsheet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Service/Utilities.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RepositoryFactoryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Grep.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/RemoteBoundaryTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Readme/Item.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Readme/Main.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Remote/ReadmeContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Snippet/Service/Snippet.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Service/DomainProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/SnippetType/Remote/Config.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Power/RepositoryConfigurationTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Spreadsheet/Exporter.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Spreadsheet/ExporterTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Spreadsheet/RowDataArray.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Spreadsheet/RowDataArrayTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Table.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Table/TableContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Table/Schema.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Table/TableContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Table/SchemaChecker.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Table/TableContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Table/Search.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Table/TableContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Table/Validator.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Table/TableContractTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/User/IdentityTrait.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/User/IdentityTraitTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Constantpaths.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/ConstantpathsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Exception/NoUserIdFoundException.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/Exception/NoUserIdFoundExceptionTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/FilterHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/FilterHelperTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Http.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/HttpTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Normalize.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/NormalizeTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Permitted/Actions.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/Permitted/ActionsTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/RepoHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/RepoHelperTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Response.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/ResponseTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/Uri.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/UriTest.php'
	],
	'VDM.Joomla/src/Componentbuilder/Utilities/UserHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Componentbuilder/Utilities/UserHelperTest.php'
	],
	'VDM.Joomla/src/Data/Action/Delete.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/Action/DeleteTest.php'
	],
	'VDM.Joomla/src/Data/Action/Insert.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/Action/InsertTest.php'
	],
	'VDM.Joomla/src/Data/Action/Load.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/Action/LoadTest.php'
	],
	'VDM.Joomla/src/Data/Action/Update.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/Action/UpdateTest.php'
	],
	'VDM.Joomla/src/Data/Factory.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Data/FactoryTest.php'
	],
	'VDM.Joomla/src/Data/Guid.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Data/ItemsTest.php'
	],
	'VDM.Joomla/src/Data/Item.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/ItemTest.php'
	],
	'VDM.Joomla/src/Data/Items.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/ItemsTest.php'
	],
	'VDM.Joomla/src/Data/Migrator/Guid.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/Migrator/GuidTest.php'
	],
	'VDM.Joomla/src/Data/MultiSubform.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/MultiSubformTest.php'
	],
	'VDM.Joomla/src/Data/Power/Item.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/Power/ItemTest.php'
	],
	'VDM.Joomla/src/Data/Subform.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/SubformTest.php'
	],
	'VDM.Joomla/src/Data/UsersSubform.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Data/UsersSubformTest.php'
	],
	'VDM.Joomla/src/Database/DefaultTrait.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Database/DefaultTraitTest.php'
	],
	'VDM.Joomla/src/Database/Delete.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Database/DeleteTest.php'
	],
	'VDM.Joomla/src/Database/Insert.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Database/InsertTest.php'
	],
	'VDM.Joomla/src/Database/Load.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Database/LoadTest.php'
	],
	'VDM.Joomla/src/Database/QuoteTrait.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Abstraction/DatabaseTest.php'
	],
	'VDM.Joomla/src/Database/Update.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Database/UpdateTest.php'
	],
	'VDM.Joomla/src/File/Agent.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/File/AgentTest.php'
	],
	'VDM.Joomla/src/File/Definition.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/File/DefinitionTest.php'
	],
	'VDM.Joomla/src/File/TypeDefinition.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/File/TypeDefinitionTest.php'
	],
	'VDM.Joomla/src/Import/Data.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/DataTest.php'
	],
	'VDM.Joomla/src/Import/Entity.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/EntityTest.php'
	],
	'VDM.Joomla/src/Import/JoinTables.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/JoinTablesTest.php'
	],
	'VDM.Joomla/src/Import/Mapper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/MapperTest.php'
	],
	'VDM.Joomla/src/Import/Message.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/MessageTest.php'
	],
	'VDM.Joomla/src/Import/ParentTable.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/ParentTableTest.php'
	],
	'VDM.Joomla/src/Import/Persistent/Entity.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/Persistent/EntityTest.php'
	],
	'VDM.Joomla/src/Import/Row.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/RowTest.php'
	],
	'VDM.Joomla/src/Import/Spreadsheet/ChunkReadFilter.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/Spreadsheet/ChunkReadFilterTest.php'
	],
	'VDM.Joomla/src/Import/Spreadsheet/FileReader.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/Spreadsheet/FileReaderTest.php'
	],
	'VDM.Joomla/src/Import/Spreadsheet/Reader.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Import/Spreadsheet/ReaderTest.php'
	],
	'VDM.Joomla/src/Interfaces/Activeregistryinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/ActiveregistryinterfaceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/DeleteInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/GuidInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/InsertInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/ItemInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/ItemsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/LoadInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/MultiSubformInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/SubformInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Data/UpdateInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Database/DefaultInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Database/DeleteInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Database/InsertInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Database/LoadInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Database/UpdateInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Database/VersioningInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/FactoryInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/File/AgentInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/File/AgentTest.php'
	],
	'VDM.Joomla/src/Interfaces/File/DefinitionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/File/DefinitionTest.php'
	],
	'VDM.Joomla/src/Interfaces/File/HandlerInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/File/PersistentManagerInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/File/TypeDefinitionInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/File/TypeDefinitionTest.php'
	],
	'VDM.Joomla/src/Interfaces/Git/ApiInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Git/Repository/ContentsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Git/Repository/TagsInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Git/Repository/WikiInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/GrepInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/AssessorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/DatabaseMessageInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/EntityInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/FileReaderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/ItemProcessInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/JoinTablesInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/MapperInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/MessageInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/ParentTableInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/PersistentEntityInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/RowInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/RowItemInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/SpreadsheetReaderInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Import/StatusInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/ModelInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/PHPConfigurationCheckerInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Readme/ItemInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Readme/MainInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Registryinterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/RegistryinterfaceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Remote/BaseInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Remote/ConfigInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Remote/Dependency/ResolverInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Remote/GetInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Remote/SetInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/SchemaCheckerInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/SchemaInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/Spreadsheet/RowDataInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/TableInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Interfaces/TableValidatorInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Joomla/src/Interfaces/InterfaceConformanceTest.php'
	],
	'VDM.Joomla/src/Model/Load.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Model/LoadTest.php'
	],
	'VDM.Joomla/src/Model/Upsert.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Model/UpsertTest.php'
	],
	'VDM.Joomla/src/Service/Data.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Service/Database.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Service/Import.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Service/Model.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Service/Table.php' => [
		'mode' => 'provider',
		'owner' => 'VDM.Joomla/src/Service/ProviderCatalogTest.php'
	],
	'VDM.Joomla/src/Spreadsheet/Header.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Spreadsheet/HeaderTest.php'
	],
	'VDM.Joomla/src/Utilities/ArrayHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/ArrayHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/Base64Helper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/Base64HelperTest.php'
	],
	'VDM.Joomla/src/Utilities/ClassHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/ClassHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/Component/Helper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/Component/HelperTest.php'
	],
	'VDM.Joomla/src/Utilities/DateHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/DateHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/FileHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/FileHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/FormHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/FormHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/GetHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/GetHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/GetHelperExtrusion.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/GetHelperExtrusionTest.php'
	],
	'VDM.Joomla/src/Utilities/GuidHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/GuidHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/JsonHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/JsonHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/MathHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/MathHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/MimeHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/MimeHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/ObjectHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/ObjectHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/SessionHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/SessionHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/String/ClassfunctionHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/String/ClassfunctionHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/String/ComponentCodeNameHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/String/ComponentCodeNameHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/String/FieldHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/String/FieldHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/String/NamespaceHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/String/NamespaceHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/String/PluginHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/String/PluginHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/String/TypeHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/String/TypeHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/StringHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/StringHelperTest.php'
	],
	'VDM.Joomla/src/Utilities/UploadHelper.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Joomla/src/Utilities/UploadHelperTest.php'
	],
	'VDM.Minify/src/Abstraction/BasicException.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/Abstraction/BasicExceptionTest.php'
	],
	'VDM.Minify/src/Abstraction/Minify.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/Abstraction/MinifyTest.php'
	],
	'VDM.Minify/src/Css.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/CssTest.php'
	],
	'VDM.Minify/src/Exceptions/FileImportException.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/Exceptions/FileImportExceptionTest.php'
	],
	'VDM.Minify/src/Exceptions/IOException.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/Exceptions/IOExceptionTest.php'
	],
	'VDM.Minify/src/JavaScript.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/JavaScriptTest.php'
	],
	'VDM.Minify/src/Path/Converter.php' => [
		'mode' => 'unit',
		'owner' => 'VDM.Minify/src/Path/ConverterTest.php'
	],
	'VDM.Minify/src/Path/Interfaces/ConverterInterface.php' => [
		'mode' => 'contract',
		'owner' => 'VDM.Minify/src/Path/Interfaces/ConverterInterfaceTest.php'
	]
];
