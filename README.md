# TRELLO2DOCJIRA

Trello to Word doc and markdown text (for pasting into JIRA). YMMV.

## Installation

This tool requires:

* PHP
* pandoc - Download from <http://pandoc.org/>

## Usage

Modify the `jello_reference.docx` to adjust how you want Word's headings, colors, and headers/footers to look. 

1. Manually export your Trello board as JSON:
    Trello > Menu > More > Print and Export > Export as JSON
1. Modify `jello.sh` to point to JSON file.
1. Run `jello.sh`

Notes:

* Ignores any Trello lists beginning with a hyphen (-).
* Turning off bold titles allows copy/paste into JIRA easier.

# Future

* Remove need for shell script (see _jira2doc_).