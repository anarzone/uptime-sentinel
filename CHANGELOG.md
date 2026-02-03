## [1.6.2](https://github.com/anarzone/uptime-sentinel/compare/v1.6.1...v1.6.2) (2026-02-03)

### Bug Fixes

* exhaustive production networking and fail-fast startup ([30768b7](https://github.com/anarzone/uptime-sentinel/commit/30768b732ab43147e227342cd007b155eba2ca7f))
* explicit php-fpm command for app service ([cbc32ea](https://github.com/anarzone/uptime-sentinel/commit/cbc32ea358888f40087d72821a6b9953719ca091))
* production database healthcheck and port 8000 ([1c3ca76](https://github.com/anarzone/uptime-sentinel/commit/1c3ca760a3ef135a28689101eb24c296aa9804bc))
* production networking and startup sequence ([6845371](https://github.com/anarzone/uptime-sentinel/commit/6845371609ff1aa755ad4e25986c8e79eb5a0228))
* production optimization and port 80 standardization ([7771fbc](https://github.com/anarzone/uptime-sentinel/commit/7771fbc575c4edc99e014620b1f28de285ff7edd))

## [1.6.1](https://github.com/anarzone/uptime-sentinel/compare/v1.6.0...v1.6.1) (2026-02-03)

### Bug Fixes

* production nginx configuration and port mapping ([344c23a](https://github.com/anarzone/uptime-sentinel/commit/344c23aadcd1f2f7b0b3091aa864d41e120b298f))

## [1.6.0](https://github.com/anarzone/uptime-sentinel/compare/v1.5.0...v1.6.0) (2026-02-03)

### Features

* premium dashboard and status page with scale fixes ([9e06ee3](https://github.com/anarzone/uptime-sentinel/commit/9e06ee3fea48e247acebc3c7c0e57a656546b3dc))
* **telemetry:** add integration tests and fix ingestion with UUID generation ([6e38cbc](https://github.com/anarzone/uptime-sentinel/commit/6e38cbcfbbcfd4a19ef854f9282e700ee7d3fce8))

### Bug Fixes

* **ci:** correct database name and use migrations in workflow ([d3416b4](https://github.com/anarzone/uptime-sentinel/commit/d3416b48f9781a08ffd69ce7a7a2cdf63ecbc210))
* **ci:** resolve test failures and add landing page redesign ([1ca1701](https://github.com/anarzone/uptime-sentinel/commit/1ca1701bac4ac1cac9f450e904862c909a5afcfc))
* **migrations:** convert ping_results creation to raw SQL ([3cc0bc9](https://github.com/anarzone/uptime-sentinel/commit/3cc0bc9a2daf4c75273c9f180e7841495226895b))
* **migrations:** include created_at in ping_results primary key for MySQL partitioning ([086c9e3](https://github.com/anarzone/uptime-sentinel/commit/086c9e3526ceefc4b552c77b2a28342d69ed961b))
* resolve double database suffix in CI ([c1530be](https://github.com/anarzone/uptime-sentinel/commit/c1530be25094aa0023df5902d7815c96c159076a))
* restore telemetry tables and update deployment config ([11e3c59](https://github.com/anarzone/uptime-sentinel/commit/11e3c59374bc50a1cb04a2c10eec754a65935c49))

## [1.5.0](https://github.com/anarzone/uptime-sentinel/compare/v1.4.0...v1.5.0) (2026-01-31)

### Features

* **telemetry:** implement resilient queue and lag monitoring command ([fd0c1e7](https://github.com/anarzone/uptime-sentinel/commit/fd0c1e79457b0119f6b51fd5c29654b603290ab3))

## [1.4.0](https://github.com/anarzone/uptime-sentinel/compare/v1.3.0...v1.4.0) (2026-01-31)

### Features

* **docker:** integrate telemetry engine with docker compose ([2871139](https://github.com/anarzone/uptime-sentinel/commit/2871139723f295a4cc49666adafba9eb61d59646))
* **telemetry:** implement core telemetry ingestion engine ([a6db60d](https://github.com/anarzone/uptime-sentinel/commit/a6db60d8c4a687d8f47fb4e7ce62792b6de89e4a))
* **telemetry:** implement three-tier aggregation and partitioning ([822a2bf](https://github.com/anarzone/uptime-sentinel/commit/822a2bfed026bd451b6df17b4bc025dc84853b32))

## [1.3.0](https://github.com/anarzone/uptime-sentinel/compare/v1.2.3...v1.3.0) (2026-01-28)

### Features

* Establish clean naming convention for database schema ([aa976ff](https://github.com/anarzone/uptime-sentinel/commit/aa976ff4f8e66624e77cb138b072413bc63c5de8))
* Implement comprehensive code quality and monitoring improvements ([7797858](https://github.com/anarzone/uptime-sentinel/commit/7797858a1a44fed2ca557e6b642fbf0b6af464d8))

## [1.3.0](https://github.com/anarzone/uptime-sentinel/compare/v1.2.3...v1.3.0) (2026-01-28)

### Features

* Add MonitorState value object for DDD compliance ([5e9c788](https://github.com/anarzone/uptime-sentinel/commit/5e9c7886f6dbfbdb835a61d642f21d9f7e221377))
* Add updateNotificationChannel method to AlertRule entity ([68025a4](https://github.com/anarzone/uptime-sentinel/commit/68025a4933b09395f40592fa8e98bcbbeddca84b))
* Establish clean naming convention for database schema ([aa976ff](https://github.com/anarzone/uptime-sentinel/commit/aa976ff4f8e66624e77cb138b072413bc63c5de8))
* Implement notification channel update in UpdateAlertRuleHandler ([012454e](https://github.com/anarzone/uptime-sentinel/commit/012454e766beeb6e618ebf40ffd88786cc21381d))

### Bug Fixes

* Add detailed exception logging to batch handler ([ab7b878](https://github.com/anarzone/uptime-sentinel/commit/ab7b8787c160489909780187ff85b501ca56cfbc))
* Add JOIN FETCH to EscalationPolicyRepository to prevent N+1 queries ([76fc43d](https://github.com/anarzone/uptime-sentinel/commit/76fc43db8c41159541058d500fd2854310cbad49))
* Add Redis error handling with graceful degradation ([cbd1cc1](https://github.com/anarzone/uptime-sentinel/commit/cbd1cc1dbd55c6e4c6c931dc27e941cc057017ae))
* Prevent N+1 queries with JOIN FETCH in AlertRuleRepository ([2cc0a3a](https://github.com/anarzone/uptime-sentinel/commit/2cc0a3aec44c480b0da8a15d431cdd286b7db5a8))

## [1.2.3](https://github.com/anarzone/uptime-sentinel/compare/v1.2.2...v1.2.3) (2026-01-25)

### Bug Fixes

* Fix tests by adding serialier support in framework ([21e4677](https://github.com/anarzone/uptime-sentinel/commit/21e467706ee04531e455413b9df57bfcdae81cf2))

## [1.2.2](https://github.com/anarzone/uptime-sentinel/compare/v1.2.1...v1.2.2) (2026-01-15)

### Bug Fixes

* Improve pre-push hook error handling and security ([e873b2b](https://github.com/anarzone/uptime-sentinel/commit/e873b2b576c453d5bfa4254c403bdffca189a698))
* Improve pre-push hook to catch issues early ([b90c7df](https://github.com/anarzone/uptime-sentinel/commit/b90c7df2c731e26c99365d8dcfe355e91eaf6c06))
* Prevent shell command injection in heredoc and improve prompts ([de42386](https://github.com/anarzone/uptime-sentinel/commit/de423865c1fc0ee673d125c88f58c47f336fa851))
* Remove duplicate code and improve error handling ([8f507fd](https://github.com/anarzone/uptime-sentinel/commit/8f507fd77e907bccc0e21f8c61d6d44eca02d8e8))

## [1.2.1](https://github.com/anarzone/uptime-sentinel/compare/v1.2.0...v1.2.1) (2026-01-15)

### Bug Fixes

* Read interactive prompt from /dev/tty to avoid stdin conflict ([248d1ab](https://github.com/anarzone/uptime-sentinel/commit/248d1aba30deb0245d12cd2a5637724f158ead0c))

## [1.2.0](https://github.com/anarzone/uptime-sentinel/compare/v1.1.4...v1.2.0) (2026-01-15)

### Features

* Add interactive pre-push code review hook ([1c480b0](https://github.com/anarzone/uptime-sentinel/commit/1c480b0d0a3222e93e53fc16d95fa49f257bd701))

## [1.1.4](https://github.com/anarzone/uptime-sentinel/compare/v1.1.3...v1.1.4) (2026-01-15)

### Bug Fixes

* Enable auto_review in pre-push config ([a0118d4](https://github.com/anarzone/uptime-sentinel/commit/a0118d41fe532e10ca84cfc0b7f900e883a6d242))
* Include deleted files in pre-push review ([e1681da](https://github.com/anarzone/uptime-sentinel/commit/e1681da56f108d28ac03c08793f55ff9329c01d8))

## [1.1.3](https://github.com/anarzone/uptime-sentinel/compare/v1.1.2...v1.1.3) (2026-01-15)

### Bug Fixes

* Handle diverged history in pre-push hook ([01f1764](https://github.com/anarzone/uptime-sentinel/commit/01f1764c7b1cdd0d908ad04c9d699cde1f859257))

## [1.1.2](https://github.com/anarzone/uptime-sentinel/compare/v1.1.1...v1.1.2) (2026-01-15)

### Bug Fixes

* Increase max-turns to allow full review completion ([5b6bbfb](https://github.com/anarzone/uptime-sentinel/commit/5b6bbfb0c68a769443306f4d01f6e244cc4aca73))
* Use settings parameter to configure GLM API endpoint ([08fa43c](https://github.com/anarzone/uptime-sentinel/commit/08fa43cf1628a003b24b27297db72e1a1f985b8c))
* Use step-level env for ANTHROPIC_BASE_URL ([37bc021](https://github.com/anarzone/uptime-sentinel/commit/37bc02133de10ede73eae2e9ee3bbe9404bd1655))

## [1.1.1](https://github.com/anarzone/uptime-sentinel/compare/v1.1.0...v1.1.1) (2026-01-14)

### Bug Fixes

* Update pre-push hook to use claude CLI and improve error handling ([85107a0](https://github.com/anarzone/uptime-sentinel/commit/85107a0dc600209063cff5ddfc908304fb8b5694))

## [1.1.0](https://github.com/anarzone/uptime-sentinel/compare/v1.0.1...v1.1.0) (2026-01-14)

### Features

* Add comprehensive project documentation and developer workflows ([f357137](https://github.com/anarzone/uptime-sentinel/commit/f3571378f8671ce16034ce0347e6f101e9363f17))

### Bug Fixes

* Rename ci workflow ([ab280b9](https://github.com/anarzone/uptime-sentinel/commit/ab280b9a6bcdb66920f03e22c3d54c50b5cdd3cb))

## [1.0.1](https://github.com/anarzone/uptime-sentinel/compare/v1.0.0...v1.0.1) (2026-01-14)

### Bug Fixes

* Add GitHub Actions permissions for releases ([3ab5a44](https://github.com/anarzone/uptime-sentinel/commit/3ab5a4407b469cade4d6120ba32330e0274800da))

## 1.0.0 (2026-01-14)

### Features

* Add semantic release automation ([4bb2a46](https://github.com/anarzone/symfony-professional-starter/commit/4bb2a466bf5ac3b2fac344316f642a341041beb2))
* Configure Symfony framework and dependencies ([460ff71](https://github.com/anarzone/symfony-professional-starter/commit/460ff7192138b22526613216336dbc668f57af67))

### Bug Fixes

* Add GitHub Actions permissions for releases ([3ab5a44](https://github.com/anarzone/symfony-professional-starter/commit/3ab5a4407b469cade4d6120ba32330e0274800da))

## 1.0.0 (2026-01-14)

### Features

* Add semantic release automation ([4bb2a46](https://github.com/anarzone/uptime-sentinel/commit/4bb2a466bf5ac3b2fac344316f642a341041beb2))
* Configure Symfony framework and dependencies ([460ff71](https://github.com/anarzone/uptime-sentinel/commit/460ff7192138b22526613216336dbc668f57af67))
