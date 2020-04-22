<?php

	define('BRAZE_API_KEY', '59169c1d-e603-4761-be84-bb34ae03f818');	// go live key
	// define('BRAZE_API_KEY', '4e243e83-a2aa-4a1c-88b2-ccbe03761f53');	// sandbox key
	
	define('NEW_USER_PATH', 'Braze/Export/NewCustomer');
	define('UPDATE_USER_PATH', 'Braze/Export/UpdateCustomer');
	define('EVENT_PATH', 'Braze/Export/Event');
	define('PURCHASE_PATH', 'Braze/Export/Purchase');
	define('DELETE_CUSTOMER_PATH', 'Braze/Export/DeleteCustomer');
	define('SUB_EMAIL_PATH', 'Braze/Export/SubEmail');
	define('SUB_SMS_PATH', 'Braze/Export/SubSMS');
	define('CAMPAIGN_PATH', 'Braze/Export/campaign');
	define('CANVAS_PATH', 'Braze/Export/canvas');
	define('LOG_PATH', 'Braze/Export/logs');

	define('BRAZE_URL_NEW_USER', 'https://rest.fra-01.braze.eu/users/alias/new');
	define('BRAZE_URL_UPDATE_USER', 'https://rest.fra-01.braze.eu/users/track');
	define('BRAZE_URL_EVENT', 'https://rest.fra-01.braze.eu/users/track');
	define('BRAZE_URL_PURCHASE', 'https://rest.fra-01.braze.eu/users/track');
	define('BRAZE_URL_DEL_USER', 'https://rest.fra-01.braze.eu/users/delete');
	define('BRAZE_URL_SUB_MAIL', 'https://rest.fra-01.braze.eu/email/status');

	define('BRAZE_URL_CAMPAIGN', 'https://rest.fra-01.braze.eu/sends/data_series');
	define('BRAZE_URL_CANVAS', 'https://rest.fra-01.braze.eu/canvas/data_summary');

	define('SEND_MAIL_TO', 'korapotu@gmail.com||nuttapol_s@apthai.com||bundit_t@apthai.com||maythad_v@apthai.com||anupong_t@apthai.com');
	// define('SEND_MAIL_TO', 'chodkeengon@gmail.com||korapotu@gmail.com');