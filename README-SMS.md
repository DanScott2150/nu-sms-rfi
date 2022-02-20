# SMS RFI Functionality

TODO: Lot's of need for cleanup & refactoring. Currently (2/17) in 'minimum viable product' mode for imminent project launch deadline. There's been a lot of headaches and curveballs on this one and right now it works and I'm terrified of changing anything. Will continue to refine and improve once we have longer-term deadlines.

## IMPORTANT NOTES ON MIGRATING FROM PREPROD TO PRODUCTION:
 - [Activation] NUEDU Forms plugin needs to be deactivated + reactivated for custom wp_sms_rfi database table to be created. Planning on moving this initialization to within the custom settings page.
 - [WP-Admin] **ALL SETTINGS FIELDS in wp-admin gravity forms for 'SMS Responder' MUST be set to valid values.** Right now there is **NO** error checking or handling built in. (Need to fix)
 - [WP-Admin] Gravity Form 'RFI Default' (form id #1) needs 'supplier ID' hidden field key set to 'supplier_id' (this was previously set to 'supplierID' on preprod which screwed things up)
 - [WP-Admin] Gravity Form 'RFI Default' (form id #1) field for 'leadgroup' hidden field needs to have 'autopopulate' enabled with parameter name 'leadgroup_auto_populate'
 - [WP-Admin] GF form needs 'supplier ID' to be field ID 61 (shouldn't need to change, just noting because it's mission-critical, for now at least)
 - [WP-Admin] GF form needs 'zipcode' to be field ID 35; 'phone number' => 34, 'country code' => 51 (shouldn't need to change, just noting because it's mission-critical, for now at least)
 - [Code] Quiq API endpoint must contain '?allowMultipleSegments=true' otherwise SMS sends with more than 160 characters will fail
 - [Code] 'Supplier ID' & 'Lead Routing group' are populated in our custom gravityforms-doublepositive plugin. SMS routing functionality depends on minor adjustments I made to those files:
	- plugins\gravityforms-doublepositive\inc\class-feed-processing.php [line 105]
	- plugins\gravityforms-doublepositive\inc\class-populate-fields.php [line 156]

## Functionality: Custom SMS RFI Follow-up
When a user submits a standard RFI form on certain pages, they receive a series of follow-up SMS text messages (as opposed to standard follow-up phone call).

Process flow:
1) User submits RFI form on business or psych-related program page
2) Form sends a custom 'supplier ID' value to DoublePositive, to prevent standard phone follow up
3) Form sends a custom 'lead routing group' value to Eloqua, which then flows through to OnDemand for the enrollment advisor team.
4) (SMS #1) Form sends an API request to Quiq, which triggers an SMS text
4a) For API requests sent outside of "business hours", the text will send the following morning. Business hours are currently set to 8am thru 6pm PST. **This is set for us by Quiq, need to contact Maile Chong <maile.chong@quiq.com> to change**
5) Form creates an entry in a custom 'wp_sms_rfi' database table, containing the user's phone number and the current timestamp
6) Plugin schedules a wp-cron task to run every 15 minutes, which checks the 'wp_sms_rfi' table for any entries older than 30 minutes.
7) (SMS #2) For any entries older than 30 minutes, a second API request is made to Quiq, triggering the second SMS text.

## Setup/Config notes:
- Custom settings menu via Forms >> Settings >> SMS Responder
	- Specify which page ID's should have SMS-enabled RFI forms
	- Manage API keys for Quiq [and other Quiq-related config settings]
	- Manage SMS text content for both messages
	- Settings still hard-coded in code: (move these to admin settings at some point)
		- 'leadgroup' => 'NUSMSPilot'
		- 'QUIQ_ENDPOINT' => https://nus.goquiq.com/api/v1/messaging/platforms/SMS/send-notification?allowMultipleSegments=true
		- Time delay for SMS #2? Enrollment group originally wanted 24 hours, then just 30 minutes. Might be best to split this into an admin setting for any future changes (would also make testing on dev environments easier)

## Quiq admin info
nus.goquiq.com
[[ ACCOUNT LOGIN DETAILS OMITTED ]]
Able to manage API keys, manage contact points, view outbound SMS's sent

## Testing/Dev notes
	- For ease of testing, change timeframe delay (both wp-cron line 60, and $diff on line 333) to 60 seconds (might be worth splitting this into wp-admin setting)
	- WP Cron can be wonky on local dev (it was for me at least), but works as intended on preprod
	- WP Cron is triggered by pageviews. Not an issue on live site due to high traffic, but on preprod/dev may need to "simulate" traffic by clicking around
	- Our config settings for Quiq authentication include API Key, Secret, Token ID, and Access Token. Our current authentication method only uses API Key + Secret, but included the other two in case we need it in the future.
	- NU Enrollment team needs to see these form submission leads somehow marked as 'SMS' in OnDemand. There was lots of confusion as to how our form submission data flows through to OnDemand, and it sounds like the enrollment team isn't entirely clear on it either.
		- Current process flow for these instances: WordPress (GravityForms) >> Eloqua >> OnDemand.
		- This is different than most GF submissions, which go: Gravity Form >> DoublePositive >> OnDemand
		- This is managed via our custom 'gravityforms-doublepositive' plugin

	- Quiq API documentation: https://developers.goquiq.com/api/docs#operation/Send%20notification
	- WP Cron API: https://developer.wordpress.org/plugins/cron/


### Misc Points of Contact:
Quiq Developer API: Maile Chong <maile.chong@quiq.com>
NU Enrollment Team/OnDemand: Claire Leong <CLeong@nu.edu>