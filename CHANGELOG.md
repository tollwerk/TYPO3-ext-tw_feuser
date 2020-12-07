# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Each entry will be separated into four possible groups: **Added**, **Removed**, **Changed** and **Fixed**.

## [0.2.0] - 2014-05-26
### Added
- The FrontendUser->registrationAction now can be called with an additional $passthrough parameter for passing data through all registration steps
- Hook: frontendUserRegistration
- Hook: frontendUserConfirmRegistration
- Hook: frontendUserRegistrationForm
- There is a debug mode which can be activated in typoscript constants editor 

## [0.1.1] - 2014-05-24
### Added

- This CHANGELOG file
- Hook *beforeBuildingFinished*, closes #1

## [0.1.0] - 2019-04-18
### Added

- Frontend user registration plugin with double-opt-in 