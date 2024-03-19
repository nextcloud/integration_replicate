# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [1.1.1] – 2024-03-19

### Added

- Image generation provider
- Text generation provider
- Admin settings to choose text/image generation model and set extra model parameters
- Psalm and php-cs checks

### Changed

- Bump min NC version to 28
- Update npm pkgs
- Use @nextcloud/vue 8

## [1.1.0] – 2024-02-28

### Changed

- Add support for Nextcloud 29

## [1.0.8] – 2023-10-126

### Changed

- Add support for Nextcloud 28
- README & info.xml: Add ethical AI rating
- Fix(l10n): Update translations from Transifex
- update nc/vue, picker modal size is now used
- improve style of audio recorder


## 1.0.6 – 2023-05-11

### Changed

- change regenerate icon @julien-nc
- disable inputs during generation/regeneration @julien-nc
- set prompt history bubble max width, add title with full text @julien-nc

### Fixed

- error text grammar fixes

## 1.0.5 – 2023-05-09

### Added

- implement OCP\SpeechToText\ISpeechToTextProvider @julien-nc
- prompt history @julien-nc

### Changed

- add link to https://replicate.com in the README so people understand what the repository is about @pbek
- move whisper model selection to admin settings @julien-nc
- always return text (no link) in the whisper picker component @julien-nc
- implement 2 step flow in image picker component, preview/adjust before submitting @julien-nc

## 1.0.3 – 2023-03-17
### Changed
- improved style
- show better errors

## 1.0.2 – 2023-03-03
### Changed
- improve design
- use more NC components

## 1.0.0 – 2022-12-19
### Added
* the app
