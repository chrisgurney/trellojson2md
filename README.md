# trellojson2md

PHP script that converts Trello's exported JSON to markdown text.

Included script examples then convert markdown to Word (via Pandoc), or Jira's flavor of markdown (Textile), though the latter may be now out of date.

## Installation

This tool requires:

* PHP
* [pandoc](https://pandoc.org/) - Optional, to convert markdown to Word.

## Usage

### Trello JSON to Markdown

1. Manually export your Trello board as JSON:
    Trello > Menu > More > Print and Export > Export as JSON
1. Modify `jello.sh` to point to JSON file.
1. Run `jello.sh`

Notes:

* Ignores any Trello lists beginning with a hyphen (-).

### Markdown to Word

Modify the `jello_reference.docx` to adjust how you want Word's headings, colors, and headers/footers to look. 

Review the relevant lines in `jello.sh` and run.

### Markdown to Jira (Texttile)

Review the relevant lines in `jello.sh` and run.

Notes:

* Turning off bold titles allows copy/paste into JIRA easier.