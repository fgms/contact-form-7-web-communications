# Contact Form 7 Web Communciations Plugin

## Introduction

This plugin adds functionality to contact form 7

* Gives the option to create "Accept/Decline" email communications.
* Adds a supervisor email for dropped email communications.
* Adds Bcc email to prevent Mod Security Errors when saving headers.
* Creates ability to add email rollups every week.
* Adds Custom Twig template function into this plugin.
* Creates a database of form communication.
* Updates Mail and Mail 2 emails to Admin and Client emails
* adds additional email tags site_url, company_name, comm_status, comm_accept_link and comm_decline_link

## shortcodes

### [wpcf7_communications_results]

Gets results of weekly communications.
```
[wpcf7_communications_results]
```

### [wpcf7_communications_action]

| Attribute | Description |
| --------- | ----------- |
| accept_message      | this message is used when admin clicks on accept link |
| decline_message     | this message is used when admin clicks on decline link |
| not_found_message   | this message is used when admin clicks on accept or decline, but entry is not valid |

```
[wpcf7_communications_action accept_message="" decline_message="" not_found_message=""]
```

## File Structure

```
├── composer.json  - for composer
├── contact-form-7-web-communications.php - plugin file
├──  README.MD -- this file
├── shortcodes
│   ├── wpcf7-communications-action.php -- this allows an action for accept and decline of email communications
│   └── wpcf7-communications-results.php -- this outputs the results of weekly email communications
└── src
    └── Fgms
        └── Communications
            ├── Controller.php - Controller
            ├── Model.php - where all the database stuff happens.
            └── View.php - display data

```
