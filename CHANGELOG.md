## [1.11.4](https://github.com/anarzone/uptime-sentinel/compare/v1.11.3...v1.11.4) (2026-02-12)

### Bug Fixes

* **docker:** bust build cache to force fresh code deployment ([a66b713](https://github.com/anarzone/uptime-sentinel/commit/a66b713b255dd8a56fd3633da5f0adfd53d3f1a7))

## [1.11.3](https://github.com/anarzone/uptime-sentinel/compare/v1.11.2...v1.11.3) (2026-02-12)

### Bug Fixes

* change User.id from guid to string type for MySQL compatibility ([35bede0](https://github.com/anarzone/uptime-sentinel/commit/35bede0a3776bb7e2dfb2ccaa62ad7869dab75ae))

## [1.11.2](https://github.com/anarzone/uptime-sentinel/compare/v1.11.1...v1.11.2) (2026-02-12)

### Bug Fixes

* **migration:** Keep users.id as VARCHAR(36) to avoid Doctrine guid type issues ([603a8bc](https://github.com/anarzone/uptime-sentinel/commit/603a8bc4a643702fb6b34b3d8b8aa604c9e4df44))

## [1.11.1](https://github.com/anarzone/uptime-sentinel/compare/v1.11.0...v1.11.1) (2026-02-12)

### Bug Fixes

* **healthcheck:** change nginx healthcheck to root path ([ac6422b](https://github.com/anarzone/uptime-sentinel/commit/ac6422b7caed13f918948fea9ee67318cef4086f))

## [1.11.0](https://github.com/anarzone/uptime-sentinel/compare/v1.10.4...v1.11.0) (2026-02-12)

### Features

* **health:** add /health endpoint for nginx healthcheck ([529b458](https://github.com/anarzone/uptime-sentinel/commit/529b4584f731f26bf801bb819f56c134f19acfc7))

## [1.10.4](https://github.com/anarzone/uptime-sentinel/compare/v1.10.3...v1.10.4) (2026-02-12)

### Bug Fixes

* **deployment:** fix container healthcheck command ([f861b46](https://github.com/anarzone/uptime-sentinel/commit/f861b4657b21ece037eb636400802781cce0ef9b))

## [1.10.3](https://github.com/anarzone/uptime-sentinel/compare/v1.10.2...v1.10.3) (2026-02-12)

### Bug Fixes

* **ci:** add CF-Access headers to deployment ([992b310](https://github.com/anarzone/uptime-sentinel/commit/992b31075906575d8b6910bee6c5d5da667bd48b))
* **ci:** fix yaml syntax and indentation in deploy job ([1e406d5](https://github.com/anarzone/uptime-sentinel/commit/1e406d53b7c6b2653c527d4bdef28b888fc9c2d5))
* **ci:** properly disable quality checks to unblock deployment ([8e12d1c](https://github.com/anarzone/uptime-sentinel/commit/8e12d1cb72285848d29bb3c653a6b19e181bb86c))

### Reverts

* Revert "fix(ci): properly disable quality checks to unblock deployment" ([be42242](https://github.com/anarzone/uptime-sentinel/commit/be4224225f8a85aaec700f68da8f929a500e9851))

## [1.10.2](https://github.com/anarzone/uptime-sentinel/compare/v1.10.1...v1.10.2) (2026-02-12)

### Bug Fixes

* **ci/migration:** clean up workflow config and fix migration syntax ([008cdee](https://github.com/anarzone/uptime-sentinel/commit/008cdee99329ea8c4fcf196183bc36fb1f05384c))
* **ci:** remove _test prefix from url ([0877e4e](https://github.com/anarzone/uptime-sentinel/commit/0877e4ea98963ec58ba4ebdeaa1235f956ab8865))

## [1.10.1](https://github.com/anarzone/uptime-sentinel/compare/v1.10.0...v1.10.1) (2026-02-12)

### Bug Fixes

* **ci:** add missing test prefix to doctrin ([f06089b](https://github.com/anarzone/uptime-sentinel/commit/f06089b57dc80f66d7723e91d83cabffb9e258e2))
* **ci:** update DATABASE_URL to use uptime_sentinel_test ([b6bcf5f](https://github.com/anarzone/uptime-sentinel/commit/b6bcf5f4edd4bc71619676b4882944570d689c46))
* **migration:** remove invalid IF EXISTS syntax from DROP INDEX ([5697d4e](https://github.com/anarzone/uptime-sentinel/commit/5697d4ea5a19a99b210282da6a0ce8dd5db880c5))
* **migrations:** make index drops conditional to fix CI failure ([d85023a](https://github.com/anarzone/uptime-sentinel/commit/d85023a05d30a61ba7ae7c36cda876d1f475d655))
* **monitoring:** align infrastructure to prod and fix alert rule cooldown type mismatch ([606df02](https://github.com/anarzone/uptime-sentinel/commit/606df02beb52821669baa950fe655807fa57cdd8))
* **tests:** resolve host environment hangs and config issues ([0410298](https://github.com/anarzone/uptime-sentinel/commit/04102987b2ef083fa8e12b93dc84ce9bf235b7f1))

## [1.10.0](https://github.com/anarzone/uptime-sentinel/compare/v1.9.1...v1.10.0) (2026-02-11)

### Features

* **docker:** add health checks to app, nginx, and redis services ([3ddfc8d](https://github.com/anarzone/uptime-sentinel/commit/3ddfc8dc480cfde873005e05a4b69a32cab29052))

## [1.9.1](https://github.com/anarzone/uptime-sentinel/compare/v1.9.0...v1.9.1) (2026-02-10)

### Bug Fixes

* **ci:** correct Coolify webhook deployment integration ([123823b](https://github.com/anarzone/uptime-sentinel/commit/123823be3abdd7915b6ae0373da0e06300f63ea4))

## [1.9.0](https://github.com/anarzone/uptime-sentinel/compare/v1.8.0...v1.9.0) (2026-02-10)

### Features

* integrate Resend mailer and secure configuration ([98b31bf](https://github.com/anarzone/uptime-sentinel/commit/98b31bf5f4124f3edea6d41afc0d38ae46c636ee))

## [1.8.0](https://github.com/anarzone/uptime-sentinel/compare/v1.7.14...v1.8.0) (2026-02-09)

### Features

* implement Monitor and Alert Management UI with Vue.js (fixed styling) ([e39f534](https://github.com/anarzone/uptime-sentinel/commit/e39f534358099bdafde082bb387a3937bd7a9b87))
* **monitoring:** implement batch creation API and DDD refactoring ([bb69113](https://github.com/anarzone/uptime-sentinel/commit/bb69113c430ca2958fd927aa66fd5d0df6bdef89))
* optimize test suite by squashing migrations ([cf4ec8a](https://github.com/anarzone/uptime-sentinel/commit/cf4ec8a35887e97c621010ae2d9c1937bc0a2577))
* standardize ownership on user UUIDs and harden security ([14ccb61](https://github.com/anarzone/uptime-sentinel/commit/14ccb61fc4536e3f7000bb47228301fe7eebcaa5))
* **ui:** enhance monitor batch selection with custom dropdown ([8280cc9](https://github.com/anarzone/uptime-sentinel/commit/8280cc991dac42484f7d7184380ff04e16712897))

## [1.7.14](https://github.com/anarzone/uptime-sentinel/compare/v1.7.13...v1.7.14) (2026-02-04)

### Bug Fixes

* correct AssetMapper compile command typo ([7b33762](https://github.com/anarzone/uptime-sentinel/commit/7b33762e3f75f37da4214207233a991359ae26ea))
* ensure assets are force-copied to shared volume on startup ([eb8b5bc](https://github.com/anarzone/uptime-sentinel/commit/eb8b5bcaaa50408b75161574888a9ce6cbb1b9c2))

## [1.7.13](https://github.com/anarzone/uptime-sentinel/compare/v1.7.12...v1.7.13) (2026-02-04)

### Bug Fixes

* compile and copy assets in Dockerfile to satisfy AssetMapper ([fd79a98](https://github.com/anarzone/uptime-sentinel/commit/fd79a98d0175234ef32bcb96f9b157cd7a2c10ba))

## [1.7.12](https://github.com/anarzone/uptime-sentinel/compare/v1.7.11...v1.7.12) (2026-02-04)

### Bug Fixes

* use shared named volume for public assets to resolve Nginx 404s ([9e51720](https://github.com/anarzone/uptime-sentinel/commit/9e517201839996d82ef6f6893ff8f7bf5b226ee0))

## [1.7.11](https://github.com/anarzone/uptime-sentinel/compare/v1.7.10...v1.7.11) (2026-02-04)

### Performance Improvements

* finalize build optimizations and robust production entrypoint ([b0c7d9b](https://github.com/anarzone/uptime-sentinel/commit/b0c7d9b50a4890e4067730dfc176a8f24f4863d3))

## [1.7.10](https://github.com/anarzone/uptime-sentinel/compare/v1.7.9...v1.7.10) (2026-02-04)

### Bug Fixes

* set APP_ENV=prod in Docker builder stage to prevent dev bundle loading ([7932d42](https://github.com/anarzone/uptime-sentinel/commit/7932d42ad22e738c288dcc6c0d6114a49d6bd9a4))

## [1.7.9](https://github.com/anarzone/uptime-sentinel/compare/v1.7.8...v1.7.9) (2026-02-04)

### Bug Fixes

* add missing PHP extensions to Docker builder stage for platform check ([4108734](https://github.com/anarzone/uptime-sentinel/commit/41087341ee2b3dee068a26b84a7ffe39545fd128))

## [1.7.8](https://github.com/anarzone/uptime-sentinel/compare/v1.7.7...v1.7.8) (2026-02-04)

### Performance Improvements

* optimize CI/CD build speed with multi-stage ARM64 builds and parallelism ([8865397](https://github.com/anarzone/uptime-sentinel/commit/886539767bc8d150062018858628d2068f39a7e3))

## [1.7.7](https://github.com/anarzone/uptime-sentinel/compare/v1.7.6...v1.7.7) (2026-02-04)

### Bug Fixes

* ensure cache and logs have correct permissions in production ([17a66bc](https://github.com/anarzone/uptime-sentinel/commit/17a66bcdd2152dbaa3ab6c5f232c4ad84dfc2887))
* run importmap:install in Dockerfile ([03da859](https://github.com/anarzone/uptime-sentinel/commit/03da859abdd7b1c7654d39f48d7b38cd7e0c80ae))

## [1.7.6](https://github.com/anarzone/uptime-sentinel/compare/v1.7.5...v1.7.6) (2026-02-04)

### Bug Fixes

* pass APP_SECRET to PHP services ([7aeba9b](https://github.com/anarzone/uptime-sentinel/commit/7aeba9b7c8788d1127afaf2d9c9ac6b559a7537c))

## [1.7.5](https://github.com/anarzone/uptime-sentinel/compare/v1.7.4...v1.7.5) (2026-02-04)

### Bug Fixes

* correct telemetry receiver and improve production entrypoint ([1c00977](https://github.com/anarzone/uptime-sentinel/commit/1c00977be3f92844a9558319e3dce3635f6265cf))

## [1.7.4](https://github.com/anarzone/uptime-sentinel/compare/v1.7.3...v1.7.4) (2026-02-04)

### Performance Improvements

* add Docker build cache for GHA ([4a454fa](https://github.com/anarzone/uptime-sentinel/commit/4a454fa75c58a59b66cf03ae5dff05e2e100ba8c))

## [1.7.3](https://github.com/anarzone/uptime-sentinel/compare/v1.7.2...v1.7.3) (2026-02-04)

### Bug Fixes

* move serializer and property-access to production dependencies ([8f82ae6](https://github.com/anarzone/uptime-sentinel/commit/8f82ae649fa1e477cb418b1ba4f876248377ab09))

## [1.7.2](https://github.com/anarzone/uptime-sentinel/compare/v1.7.1...v1.7.2) (2026-02-04)

### Bug Fixes

* handle REDIS_DSN in entrypoint and compose ([fe6216a](https://github.com/anarzone/uptime-sentinel/commit/fe6216aac5195927e1d65ca5da0fdcb8c175d01b))

## [1.7.1](https://github.com/anarzone/uptime-sentinel/compare/v1.7.0...v1.7.1) (2026-02-04)

### Bug Fixes

* build images for ARM64 architecture ([4b851cd](https://github.com/anarzone/uptime-sentinel/commit/4b851cd7a0de8f0f25e2ba263fa061e784142ff2))

## [1.7.0](https://github.com/anarzone/uptime-sentinel/compare/v1.6.7...v1.7.0) (2026-02-04)

### Features

* build and push pre-built images to GHCR for more reliable deployment ([69b11fe](https://github.com/anarzone/uptime-sentinel/commit/69b11fe1e47df54aca18dfc2e424523534808509))

## [1.6.7](https://github.com/anarzone/uptime-sentinel/compare/v1.6.6...v1.6.7) (2026-02-04)

### Bug Fixes

* use Bearer token auth for Coolify API deployment ([ee2e222](https://github.com/anarzone/uptime-sentinel/commit/ee2e222b1ed688ccdb6876a96f339a82b7d6a24b))

## [1.6.6](https://github.com/anarzone/uptime-sentinel/compare/v1.6.5...v1.6.6) (2026-02-04)

### Bug Fixes

* use port 8080 to avoid conflicts with Coolify (8000) and system nginx (80) ([b4911d6](https://github.com/anarzone/uptime-sentinel/commit/b4911d6558a2a6d7019d5864da83d4dedc162143))

## [1.6.5](https://github.com/anarzone/uptime-sentinel/compare/v1.6.4...v1.6.5) (2026-02-03)

### Bug Fixes

* remove port 80 conflict, use only 8000 ([851211d](https://github.com/anarzone/uptime-sentinel/commit/851211d34aa3b5812497daa07c399bf2b55f959f))

## [1.6.4](https://github.com/anarzone/uptime-sentinel/compare/v1.6.3...v1.6.4) (2026-02-03)

### Bug Fixes

* bake nginx config into Docker image for Coolify compatibility ([a9b6264](https://github.com/anarzone/uptime-sentinel/commit/a9b626400d1e9e7ead75843d689f744b6666d01b))

## [1.6.3](https://github.com/anarzone/uptime-sentinel/compare/v1.6.2...v1.6.3) (2026-02-03)

### Bug Fixes

* **ci:** add retries and verbose logging to deployment webhook ([b26a711](https://github.com/anarzone/uptime-sentinel/commit/b26a71126c61ebb51de1f2a775365a962a6e9530))

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
