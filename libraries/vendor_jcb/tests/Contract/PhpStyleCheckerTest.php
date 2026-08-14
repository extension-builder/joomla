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

namespace VDM\Tests\Contract;


use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use VDM\Tests\Support\FilesystemTestCase;
use VDM\Tests\Support\PhpStyleChecker;


/**
 * Dependency-free PHP contribution style guard contracts.
 *
 * @since  1.0.0
 */
#[CoversNothing]
final class PhpStyleCheckerTest extends FilesystemTestCase
{
	/**
	 * Report exactly the rules selected by each focused fixture.
	 *
	 * @param   string         $path           Diagnostic path.
	 * @param   string         $source         Complete fixture source.
	 * @param   array<string>  $expectedRules  Expected stable rule identifiers.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	#[DataProvider('styleCases')]
	public function testFocusedStyleFixtures(
		string $path,
		string $source,
		array $expectedRules
	): void
	{
		$actualRules = self::diagnosticRules(
			PhpStyleChecker::inspectSource($source, $path)
		);
		sort($expectedRules, SORT_STRING);

		$this->assertSame($expectedRules, $actualRules);
	}

	/**
	 * Supply readable source and byte-edge fixtures without storing bad bytes.
	 *
	 * @return  iterable<string, array{string, string, array<string>}>
	 * @since   1.0.0
	 */
	public static function styleCases(): iterable
	{
		$cases = require __DIR__ . '/Fixtures/PhpStyle/cases.php.fixture';

		if (!is_array($cases))
		{
			throw new RuntimeException('PHP style fixtures must return an array.');
		}

		foreach ($cases as $name => $case)
		{
			if (!is_string($name)
				|| !is_array($case)
				|| !is_string($case['path'] ?? null)
				|| !is_string($case['source'] ?? null)
				|| !is_array($case['rules'] ?? null))
			{
				throw new RuntimeException('Malformed PHP style fixture: ' . (string) $name);
			}

			yield $name => [$case['path'], $case['source'], $case['rules']];
		}
	}

	/**
	 * Filter local style diagnostics to added lines in a modified file.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testModifiedFileStyleIssuesAreLimitedToAddedLines(): void
	{
		$source = "<?php\nif (true) {\n}\n";

		$this->assertSame(
			['opening-brace'],
			self::diagnosticRules(
				PhpStyleChecker::inspectSource($source, 'fixtures/Modified.php', [2 => true])
			)
		);
		$this->assertSame(
			[],
			PhpStyleChecker::inspectSource($source, 'fixtures/Modified.php', [3 => true])
		);
	}

	/**
	 * Anchor an extra EOF newline to its Git-added blank line.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testExtraTerminalNewlineUsesTheAddedBlankLine(): void
	{
		$errors = PhpStyleChecker::inspectSource(
			"<?php\n\$value = 1;\n\n",
			'fixtures/ExtraNewline.php',
			[3 => true]
		);

		$this->assertCount(1, $errors);
		$this->assertStringContainsString(':3: [terminal-newline]', $errors[0]);
	}

	/**
	 * Keep syntax and terminal integrity visible even for deletion-only changes.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testWholeFileIntegrityRulesAreNotHiddenByAnEmptyLineMap(): void
	{
		$errors = PhpStyleChecker::inspectSource(
			"<?php\nif (\n",
			'fixtures/Broken.php',
			[]
		);

		$this->assertSame(['syntax'], self::diagnosticRules($errors));
	}

	/**
	 * Discover Git additions, copies, renames, and added lines in modifications.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testGitComparisonDiscoversAcmrFilesAndFiltersModifiedLines(): void
	{
		$repository = $this->temporaryPath();
		$this->runGit($repository, ['init', '--quiet']);
		$this->runGit($repository, ['config', 'user.name', 'JCB Tests']);
		$this->runGit($repository, ['config', 'user.email', 'tests@example.invalid']);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/VDM.Joomla/src/Original.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/VDM.Joomla/src/Modified.php',
			"<?php\nif (true) {\n}\n\n\$value = 1;\n"
		);
		$this->runGit($repository, ['add', '.']);
		$this->runGit($repository, ['commit', '--quiet', '-m', 'base']);
		$baseSha = trim($this->runGit($repository, ['rev-parse', 'HEAD']));

		$this->createTemporaryDirectory('libraries/vendor_jcb/VDM.Joomla.Gitea/src');
		$this->assertTrue(
			copy(
				$this->temporaryPath('libraries/vendor_jcb/VDM.Joomla/src/Original.php'),
				$this->temporaryPath('libraries/vendor_jcb/VDM.Joomla.Gitea/src/Copied.php')
			)
		);
		$this->assertTrue(
			rename(
				$this->temporaryPath('libraries/vendor_jcb/VDM.Joomla/src/Original.php'),
				$this->temporaryPath('libraries/vendor_jcb/VDM.Joomla/src/Renamed.php')
			)
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/VDM.Joomla/src/Modified.php',
			"<?php\nif (true) {\n}\n\n\$value = 1;\n\n\$callback = function () {\n};\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/VDM.Minify/src/Upstream.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/Support/ProjectOwned.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/vendor/Ignored.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/.runtime/Ignored.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/build/Ignored.php',
			"<?php\n\$value = 1;\n"
		);
		$this->runGit($repository, ['add', '.']);
		$this->runGit($repository, ['commit', '--quiet', '-m', 'contribution']);

		$changes = PhpStyleChecker::changedPhpFilesSince($repository, $baseSha);
		$this->assertSame(
			[
				'libraries/vendor_jcb/VDM.Joomla.Gitea/src/Copied.php',
				'libraries/vendor_jcb/VDM.Joomla/src/Modified.php',
				'libraries/vendor_jcb/VDM.Joomla/src/Renamed.php',
				'libraries/vendor_jcb/VDM.Minify/src/Upstream.php',
				'libraries/vendor_jcb/tests/Support/ProjectOwned.php'
			],
			array_keys($changes)
		);
		$this->assertSame('M', $changes['libraries/vendor_jcb/VDM.Joomla/src/Modified.php']['status']);
		$this->assertIsArray(
			$changes['libraries/vendor_jcb/VDM.Joomla/src/Modified.php']['added_lines']
		);

		foreach ($changes as $path => $change)
		{
			if ($path === 'libraries/vendor_jcb/VDM.Joomla/src/Modified.php')
			{
				continue;
			}

			$this->assertContains($change['status'], ['A', 'C', 'R']);
			$this->assertNull($change['added_lines']);
		}

		$modifiedIssues = PhpStyleChecker::inspectFile(
			$this->temporaryPath('libraries/vendor_jcb/VDM.Joomla/src/Modified.php'),
			'libraries/vendor_jcb/VDM.Joomla/src/Modified.php',
			$changes['libraries/vendor_jcb/VDM.Joomla/src/Modified.php']['added_lines']
		);
		$this->assertCount(1, $modifiedIssues);
		$this->assertStringContainsString(':7: [opening-brace]', $modifiedIssues[0]);
	}

	/**
	 * Discover only project-owned PHP in no-base test mode.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testNoBaseDiscoveryExcludesDependenciesRuntimeAndGeneratedFiles(): void
	{
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/Support/Owned.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/VDM.Minify/src/OwnedTest.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/vendor/Dependency.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/.runtime/Runtime.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/build/Generated.php',
			"<?php\n\$value = 1;\n"
		);
		$this->writeTemporaryFile(
			'libraries/vendor_jcb/tests/.phpunit.cache/Cached.php',
			"<?php\n\$value = 1;\n"
		);

		$this->assertSame(
			[
				'libraries/vendor_jcb/tests/Support/Owned.php',
				'libraries/vendor_jcb/tests/VDM.Minify/src/OwnedTest.php'
			],
			PhpStyleChecker::firstPartyTestPhpFiles($this->temporaryPath())
		);
	}

	/**
	 * Preserve Minify production source while enforcing its project-owned tests.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function testMinifyProductionIsTheOnlyRequestedPreservationException(): void
	{
		$this->assertFalse(
			PhpStyleChecker::isStyleInScope(
				'libraries/vendor_jcb/VDM.Minify/src/Upstream.php'
			)
		);
		$this->assertTrue(
			PhpStyleChecker::isStyleInScope(
				'libraries/vendor_jcb/tests/VDM.Minify/src/UpstreamTest.php'
			)
		);
		$this->assertTrue(
			PhpStyleChecker::isStyleInScope(
				'libraries/vendor_jcb/VDM.Joomla.Git/src/Repository/Contents.php'
			)
		);
	}

	/**
	 * Extract stable rule identifiers from formatted diagnostics.
	 *
	 * @param   array<string>  $diagnostics  Formatted checker diagnostics.
	 *
	 * @return  array<string>
	 * @since   1.0.0
	 */
	private static function diagnosticRules(array $diagnostics): array
	{
		$rules = [];

		foreach ($diagnostics as $diagnostic)
		{
			if (preg_match('/\[([a-z-]+)\]/', $diagnostic, $match) !== 1)
			{
				throw new RuntimeException('Malformed PHP style diagnostic: ' . $diagnostic);
			}

			$rules[$match[1]] = true;
		}

		$rules = array_keys($rules);
		sort($rules, SORT_STRING);

		return $rules;
	}

	/**
	 * Run Git without invoking a shell.
	 *
	 * @param   string         $repository  Repository directory.
	 * @param   array<string>  $arguments   Git arguments.
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	private function runGit(string $repository, array $arguments): string
	{
		$command = array_merge(['git', '-C', $repository], $arguments);
		$descriptors = [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		];
		$process = proc_open($command, $descriptors, $pipes, $repository);

		if (!is_resource($process))
		{
			throw new RuntimeException('Unable to start Git test process.');
		}

		$output = stream_get_contents($pipes[1]);
		$errorOutput = stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		if ($exitCode !== 0)
		{
			throw new RuntimeException('Git test process failed: ' . trim((string) $errorOutput));
		}

		return (string) $output;
	}
}
