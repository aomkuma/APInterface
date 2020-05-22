<?php

# Braze Export
define('BRAZE_API_KEY', '59169c1d-e603-4761-be84-bb34ae03f818'); // go live key
// define('BRAZE_API_KEY', '4e243e83-a2aa-4a1c-88b2-ccbe03761f53');	// sandbox key

define('NEW_USER_PATH', 'Braze/Export/NewCustomer');
define('UPDATE_USER_PATH', 'Braze/Export/UpdateCustomer');
define('EVENT_PATH', 'Braze/Export/Event');
define('PURCHASE_PATH', 'Braze/Export/Purchase');
define('DELETE_CUSTOMER_PATH', 'Braze/Export/DeleteCustomer');
define('SUB_EMAIL_PATH', 'Braze/Export/SubEmail');
define('SUB_SMS_PATH', 'Braze/Export/SubSMS');
define('CAMPAIGN_PATH', 'Braze/Export/campaign');

define('BRAZE_URL_NEW_USER', 'https://rest.fra-01.braze.eu/users/alias/new');
define('BRAZE_URL_UPDATE_USER', 'https://rest.fra-01.braze.eu/users/track');
define('BRAZE_URL_EVENT', 'https://rest.fra-01.braze.eu/users/track');
define('BRAZE_URL_PURCHASE', 'https://rest.fra-01.braze.eu/users/track');
define('BRAZE_URL_DEL_USER', 'https://rest.fra-01.braze.eu/users/delete');
define('BRAZE_URL_SUB_MAIL', 'https://rest.fra-01.braze.eu/email/status');

define('LOG_PATH', 'Braze/Export/logs');
# End Braze Export

# Braze Landing
define('LANDING_PATH', 'Braze/Landing');
# End Braze Landing

#Facebook offline conversion
define('OFFLINE_EVENT_CONFIG', ['Purchase'=>
									[
										'event_set_id'=>642418166540775,
										'path'=>'FacebookOfflineConversion/Purchase',
										'eventname'=>'Purchase'
									],
	                                'Lead'=>
	                                [
	                                	'event_set_id'=>233329861241718,
	                                	'path'=>'FacebookOfflineConversion/Walk',
	                                	'eventname'=>'Lead'
	                                ]
	                            ]);

define('FACEBOOKTOKEN', 'EAAIOH9wYP14BAGY5cWZCNt63NtQ7rJdcmz0nKyCpkNWXWWaICCpjWqXdh2VpEvXD47AW6yUatean4ZB6P5k8yelzXGsQ8oDpFhKUCyYroWZB5tUQEOF6PGWl3klZB2Xw7t9y8VZCkSZBGTkfODTLsFCXwnj8IWeZCCyLZCTVWacSyAAtUJLZATp4qLUn71OTqPx0n4ZCIZCFPRGKAZDZD');

define('FACEBOOKOFFLINE_LOG', 'FacebookOfflineConversion/log');
#Facebook offline conversion

#Email sent to persons
define('SEND_MAIL_TO', 'korapotu@gmail.com||nuttapol_s@apthai.com||bundit_t@apthai.com||maythad_v@apthai.com||anupong_t@apthai.com');
#Email sent to persons