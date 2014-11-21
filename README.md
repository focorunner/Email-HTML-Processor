Email-HTML-Processor
====================

Fixes conditions in HTML code that that can cause email display issues.

Uses Simple HTML DOM parser librar http://simplehtmldom.sourceforge.net/, and a custom CSS parsing function.

This was a weekend project a couple of years ago, and done to streamline correction of issues caused by inexperienced HTML email designers trying to create HTML emails using code created for website use. Email clients are limited in rendering capability, and there are some "tricks of the trade" that are built into this app, to optionally make a range of changes to either fix or troubleshoot email client display problems.

This turned out to be fairly robust, so I never had to do additional work on it after it was written. If I do ever had cause to revisit, I would probably put most of my effort into the UI, and update to the latest Simple HTML DOM parser library, as well as making the code more efficent. 

