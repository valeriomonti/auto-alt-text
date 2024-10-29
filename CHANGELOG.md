# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [v2.2.0] - 2024-10-28

### Changed
- Allow users to select the preferred Openai model for generating alt text

## [v2.1.0] - 2024-08-19

### Changed
- Allow users to generate the alt text for each individual image in the media library

## [v2.0.0] - 2024-06-11

### Changed
- Set a maximum timeout of 90 seconds for OpenAI requests
- Implement the gpt-4o model for generating alt text
- Implement gpt-4-turbo template for fallback alt text generation
- Remove gpt-4 and gpt-3.5 templates as fallback options


## [v1.3.0] - 2024-05-02

### Changed
- Implement bulk alt text generation for images already in the media library
- Update Vite package
- Implement github actions for automatic release

## [v1.2.4] - 2024-03-03

### Changed
- Code refactoring
- DB security improvements


## [v1.2.3] - 2024-02-15

### Changed
- Code refactoring

## [v1.2.2] - 2024-02-07

### Added
- Database log management

### Changed
- Escape all output
- Add AATXT_ prefix
- Update Vite dependencies

### Removed
- File log management

## [v1.1.0] - 2023-12-19

- Initial release

 