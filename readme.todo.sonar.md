# SonarCloud findings review

**Project:** `gundestrup_j2xml`

**Status:** Reviewed against the SonarCloud REST API on 2026-09-15 —
**RESOLVED / OUTDATED** (superseded by continuous CI-triggered analysis)

> **Completion note (2026-09-16):** Every item in this review has been
> resolved or is now tracked live by SonarCloud's automatic per-push
> analysis, making this snapshot obsolete:
>
> - [x] `php:S2612` open finding (`coverage-prepend.php` `0777`) — **closed**
>       (fixed to `0700`; no longer open in current analysis).
> - [x] "675 issues" discrepancy — **explained** (stale dashboard scope;
>       verified count was 190 historical records, 189 closed).
> - [x] `tests/**` exclusion — configured; the only remaining open issues are
>       5 `php:S2077` findings in `tests/scripts/db-query.php`, handled via
>       SonarCloud Source File Exclusions / "Accepted" marking.
> - [x] Coverage pipeline — **replaced**: reports are now merged by
>       `tests/scripts/merge-clover.php` and uploaded as a single report
>       (Codecov reports 75.43%); the 73.32% integration-only figure below is
>       historical.
>
> This file is retained in git history only; it is deleted from the working
> tree because SonarCloud's continuous analysis is now the live source of
> truth.

## Current SonarCloud state

The current SonarCloud API does not report 675 active issues for this project.
The verified counts are:

- **190 total historical issue records** returned by `/api/issues/search`.
- **189 closed**.
- **1 open**.
- **0 open bugs** in the project measures.
- **0 open code smells** in the project measures.
- **1 open vulnerability** in the project measures.

The all-status severity facets are:

- 27 blocker
- 159 major
- 2 critical
- 2 minor

The all-status type facets are:

- 175 code smells
- 10 vulnerabilities
- 5 bugs

The 675 figure therefore appears to be from a different dashboard scope, an
older analysis/history view, another branch, or a stale project result. It is
not the current active-issue count returned for the configured project key.

## Remaining open finding

The only open finding is:

- **Rule:** `php:S2612`
- **Severity:** Major
- **Type:** Vulnerability/security hotspot
- **File:** `tests/scripts/coverage-prepend.php`
- **Line:** 35 in the historical analysis
- **Message:** `Make sure this permission is safe.`
- **Cause:** The coverage collector created `/tmp/j2xml-cov` with mode `0777`.

The code was changed to create the directory with mode `0700` instead. The
repository's Sonar configuration also excludes `tests/**`, so a fresh Sonar
analysis is required to confirm that this historical finding closes. No active
production-code Sonar findings remain in the current API result.

## Historical closed findings

The 189 closed records are historical findings already addressed in the
repository, including:

- ShellCheck/Sonar shell safety findings fixed by replacing `[` with `[[`.
- PHP 8.4/8.5 compatibility findings.
- Deprecated XML-RPC and legacy API findings.
- Duplicate-code and assignment-in-condition findings.
- JavaScript quality findings in the active source bundle.
- Security rules addressed through code changes or documented suppressions.

These should not be reimplemented or reopened merely because they remain in
the SonarCloud historical issue response.

## Coverage-related review

The integration coverage report currently records:

- **2,723 covered executable lines**
- **3,714 executable lines in the report**
- **73.32% line coverage**
- **64 active J2XML files**

The coverage merger now includes the previously omitted Webservices API path:

```text
api/components/com_j2xml/
```

The integration report is an observed-line metric. The merger emits files that
appear in PCOV dumps, so active files never loaded by the integration suite are
not automatically added as zero-coverage files.

The largest active-code gaps are documented in
`readme.todo.deadcode.md`. The most valuable new unit coverage was added for:

- Numeric user-group and access-level lookup short-circuits.
- Normal date normalization through Joomla's date API.
- Successful tag-path lookup conversion.
- Tag lookup database-failure handling.

The tag database-failure test also found and fixed an actual namespace bug:
`catch (RuntimeException $e)` was changed to `catch (\\RuntimeException $e)`.

## Verification

After the coverage and test changes:

- PHPUnit: **69 tests, 128 assertions passed**, with no PHP 8.5 reflection deprecations.
- PHPStan: passed.
- Semgrep: passed.
- ShellCheck: passed.
- PHP lint: passed on PHP 8.4 and PHP 8.5.
- MySQL integration: **97/97 passed** on Joomla 5 and Joomla 6, including PHP warning/deprecation checks.
- PostgreSQL smoke tests: passed.
- Integration coverage: **2,723/3,714 lines (73.32%)**.

A new SonarCloud analysis should be run after these changes. The expected
result is one fewer open issue, with the `php:S2612` coverage-directory finding
closed.
