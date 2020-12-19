<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace phpbbservices\filterbycountry\constants;

class constants {

	const ACP_FBC_COUNTRY_NOT_FOUND = 'WO';	// World, so unknown
	const ACP_FBC_NO_LIMIT_VALUE = 1;
	const ACP_FBC_LAST_QUARTER_VALUE = 2;
	const ACP_FBC_LAST_MONTH_VALUE = 3;
	const ACP_FBC_LAST_TWO_WEEKS_VALUE = 4;
	const ACP_FBC_LAST_WEEK_VALUE = 5;
	const ACP_FBC_LAST_DAY_VALUE = 6;
	const ACP_FBC_LAST_12_HOURS_VALUE = 7;
	const ACP_FBC_LAST_6_HOURS_VALUE = 8;
	const ACP_FBC_LAST_3_HOURS_VALUE = 9;
	const ACP_FBC_LAST_1_HOURS_VALUE = 10;
	const ACP_FBC_LAST_30_MINUTES_VALUE = 11;
	const ACP_FBC_LAST_15_MINUTES_VALUE = 12;

	const ACP_FBC_REQUEST_ALLOW = 0;
	const ACP_FBC_REQUEST_RESTRICT = 1;
	const ACP_FBC_REQUEST_OUTSIDE = 2;

	const FBC_COUNTRY_CODES = ['AF','AX','AL','DZ','AS','AD','AO','AI','AQ','AG','AR','AM','AW','AU','AT','AZ',
							'BS','BH','BD','BB','BY','BE','BZ','BJ','BM','BT','BO','BQ','BA','BW','BV','BR','IO','BN',
							'BG','BF','BI','KH','CM','CA','CV','KY','CF','TD','CL','CN','CX','CC','CO','KM','CG','CD',
							'CK','CR','CI','HR','CU','CW','CY','CZ','DK','DJ','DM','DO','EC','EG','SV','GQ','ER','EE',
							'ET','FK','FO','FJ','FI','FR','GF','PF','TF','GA','GM','GE','DE','GH','GI','GR','GL','GD',
							'GP','GU','GT','GG','GN','GW','GY','HT','HM','VA','HN','HK','HU','IS','IN','ID','IR','IQ',
							'IE','IM','IL','IT','JM','JP','JE','JO','KZ','KE','KI','KP','KR','KW','KG','LA','LV','LB',
							'LS','LR','LY','LI','LT','LU','MO','MK','MG','MW','MY','MV','ML','MT','MH','MQ','MR','MU',
							'YT','MX','FM','MD','MC','MN','ME','MS','MA','MZ','MM','NA','NR','NP','NL','NC','NZ','NI',
							'NE','NG','NU','NF','MP','NO','OM','PK','PW','PS','PA','PG','PY','PE','PH','PN','PL','PT',
							'PR','QA','RE','RO','RU','RW','BL','SH','KN','LC','MF','PM','VC','WS','SM','ST','SA','SN',
							'RS','SC','SL','SG','SX','SK','SI','SB','SO','ZA','GS','SS','ES','LK','SD','SR','SJ','SZ',
							'SE','CH','SY','TW','TJ','TZ','TH','TL','TG','TK','TO','TT','TN','TR','TM','TC','TV','UG',
							'UA','AE','GB','US','UM','UY','UZ','VU','VE','VN','VG','VI','WF','EH','YE','ZM','ZW'];

}
