These Joomla 5 fixtures were generated from the production PHP at PR 61's base,
`e54c39eaf8803d614a522b651507fef0536f81c2`, with the linked component and stored
`[[[API_ROUTES_METHOD]]]` record in `LinkedPluginTest::compileLinkedPlugin()`.

The baseline run loaded `VDM\Joomla` production classes from a separate archive
of that commit, using the current test harness and its pinned Joomla 6.1.2
dependencies. The target remained Joomla 5. The two files retain emitted source
locations with compiler debug comments enabled and disabled. The test compares
the entire output byte for byte; it does not regenerate these fixtures.

The emitted Joomla 5 handler receives `BeforeApiRouteEvent` and keeps its prior
class contract without a generated subscriber map. Joomla 6's native subscriber
map and actual dispatched routes are checked separately in `LinkedPluginTest`.
