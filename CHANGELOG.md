## [Unreleased]
### Added
- Added new configuration parameters: connection_timeout, read_write_timeout, heartbeat.

### Changed
- Change exception logging in ConsumerRunCommand.

## [2.0.0] - 2021-04-07
### Changed
- [BC BREAK] Constants moved from `ConsumerInterface` to `AbstractConsumer`.

## [1.0.1] - 2021-04-01
### Changed
- Changed return value usage from using Command::SUCCESS constant to scalar. 
- Commands changed: 
  * UpdateDefinitionCommand
  * ConsumerListCommand
  * ConsumerRunCommand

## [1.0.0] - 2021-03-04
### Added
- Added retry exchange for rewind message in queue with delay.
- Added config parameters `idle_timeout` and `wait_timeout`.
- Added publishers: `FifoPublisher`, `DelayPublisher`, `FifoPublisher`, `DeduplicatePublisher`, `DeduplicateDelayPublisher`.

### Changed
- Optimized receiving a batch of messages in `ConsumerRunCommand`.
- Extended supported queue types by `Delay`, `Deduplicate`.

### Fixed
- Fix rewind and release partial messages by delivery tag. Changed `ReleasePartialException`, `RewindDelayPartialException`, `RewindPartialException`.

## [0.1.1] - 2021-01-14
### Changed
- Change license type.

## [0.1.0] - 2021-01-14
### Added
- The first basic version of the bundle.
