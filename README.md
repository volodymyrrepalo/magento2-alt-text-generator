# Alt Text Generator

## Contents

- [Overview](#overview)
- [What it scans](#what-it-scans)
- [Workflow](#workflow)
- [Admin](#admin)
- [Configuration](#configuration)
- [Scheduled monitoring](#scheduled-monitoring)

## Overview

Alt Text Generator is a Magento 2 admin tool that finds images without alternative text in storefront content and fills them in with AI-generated descriptions, under merchant control.

It scans product descriptions, CMS pages, and CMS blocks for images that have a missing or empty `alt` value. A nightly cron job records the gaps without changing content. From the admin grid, an editor can review each record by store, generate alt text, preview it, and then write the updated HTML back to the original content.

Generation uses ChatGPT, with the image and nearby heading text as context. Previously generated texts are reused for the same image, the API key is stored encrypted, and a daily generation limit caps usage. Access is limited by admin ACL.

## What it scans

The module looks at HTML that already contains images, in three places:

- Store-scoped product descriptions
- Active CMS pages
- Active CMS blocks

It treats an image as missing alt text when the `alt` attribute is absent or empty. Page Builder list items that store the description in `image_alt` are handled the same way. Inline `data:` images are skipped.

Heading text (`h1`, then `h2`, then `h3`) is kept as SEO context and sent with the image when a new description is requested.

## Workflow

Work is split into three steps so content is never rewritten until someone asks for it.

**Scan.** Read the HTML, count images that still need alt text, and save that status. No descriptions are generated and no content is changed.

**Generate.** For images that still have no saved description, ask ChatGPT and store the result against the image source. If a description was already generated for that image, it is reused and ChatGPT is not called again.

**Fix.** Apply the generated descriptions to the original HTML and save it back to the product description, CMS page, or CMS block. The record is scanned again afterward so the missing-alt count reflects the updated content.

If the HTML does not change, nothing is written to the content tables.

## Admin

The grid is under **Marketing → SEO → Missing Alt Texts**.

Each row is one content record (product description, CMS page, or CMS block) in one store view. It shows how many images still lack alt text, how many descriptions have been generated, and notes from the last scan or generation.

From a row you can scan again, generate descriptions, or apply them. The same actions are available as mass actions. The edit screen lists each image next to the alt text that was generated for it.

![Screenshot](/grid-demo.png)

## Configuration

Settings are under **Stores → Configuration → Alt Text Generator**.

- **Run Alt Text Monitoring by Cron** turns the nightly scan on or off. The scan only reports missing alt text.
- **Daily Limit for Generated Alt Texts** caps how many new ChatGPT descriptions can be created per day.
- **Key** is the OpenAI API key. It is stored encrypted.
- **Instructions for ChatGPT** is the prompt sent together with the image and the SEO context taken from the content headings.

## Scheduled monitoring

When cron monitoring is enabled, the job `scan_html_values_for_missing_alt_text` runs every day at 03:00. It only scans. Generating descriptions and writing them back into content stays a manual admin action.
