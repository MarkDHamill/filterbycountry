<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2020, Mark D. Hamill, https://www.phpbbservices.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'PLURAL_RULE'						=> 1,
	'ACP_FBC_ALLOW'						=> 'Allow',
	'ACP_FBC_ALLOWED'					=> 'Allowed page requests',
	'ACP_FBC_ALLOW_OUT_OF_COUNTRY_LOGINS'	=> 'Allow registered users to login from restricted countries',
	'ACP_FBC_ALLOW_OUT_OF_COUNTRY_LOGINS_EXPLAIN'	=> 'This allows users traveling out of their countries to login. Other access to the board is prohibited.',
	'ACP_FBC_ALLOW_RESTRICT'			=> 'Allow or restrict the selected countries',
	'ACP_FBC_ALLOW_RESTRICT_EXPLAIN'	=> 'If set to allow, connections originating in the countries you select are allowed. If set to restrict, connections from all countries are allowed except those you select.',
	'ACP_FBC_CHANGE_REPORT_RANGE'		=> 'Change report range',
	'ACP_FBC_COUNTRIES'					=> 'Select one or more country codes',
	'ACP_FBC_COUNTRIES_EXPLAIN'			=> 'Hold the CTRL down (Command on a Mac) to select multiple country codes. You can select a range of countries by holding down the SHIFT key while selecting the first to last countries in the range.',
	'ACP_FBC_COUNTRY_A_Z'				=> 'Country, A-Z',
	'ACP_FBC_COUNTRY_Z_A'				=> 'Country, Z-A',
	'ACP_FBC_COUNTRY_ALLOWED_ASC'		=> 'Allowed page requests, least to greatest',
	'ACP_FBC_COUNTRY_ALLOWED_DESC'		=> 'Allowed page requests, greatest to least',
	'ACP_FBC_COUNTRY_NAME'				=> 'Country name',
	'ACP_FBC_COUNTRY_RESTRICTED_ASC'	=> 'Restricted page requests, least to greatest',
	'ACP_FBC_COUNTRY_RESTRICTED_DESC'	=> 'Restricted page requests, greatest to least',
	'ACP_FBC_CREATE_DATABASE_ERROR'		=> 'Unable to create the MaxMind country codes database. This may be due to insufficient permissions or an invalid license key. The file permissions for the forum’s /store/phpbbservices folder should be set to publicly writeable (777 on Unix-based systems).',
	'ACP_FBC_CURRENT_RANGE'				=> 'Range of statistics shown',
	'ACP_FBC_DATES_BEGINNING'			=> 'Or pick an absolute range of dates beginning',
	'ACP_FBC_DATES_ENDING'				=> 'and ending',
	'ACP_FBC_DENY_ACCESS'				=> 'Your access is denied due to the country code(s) assigned to your IP address(es): %1$s.',
	'ACP_FBC_DENY_ACCESS_LOGIN'			=> 'Your access is denied due to the country code(s) assigned to your IP address(es): %1$s. However, actively registered users may <a href="%2$s">login</a>.',
	'ACP_FBC_DOWNLOAD_ERROR'			=> 'Could not download: %1$s',
	'ACP_FBC_EFFECTIVELY_DISABLED'		=> 'To avoid locking down your board, all traffic is currently allowed. This can occur if no countries were selected. Please change your settings. If you want to do this permanently, please disable this extension.',
	'ACP_FBC_FROM'						=> 'from ',
	'ACP_FBC_IGNORE'					=> 'Ignore',
	'ACP_FBC_IGNORE_BOTS'				=> 'Ignore known bots',
	'ACP_FBC_IGNORE_BOTS_EXPLAIN'		=> 'If yes, statistics will not include your known bots, such as popular search engines. The bot might still be able to read the page depending on your other settings. You can see a list of bots in Manage groups.',
	'ACP_FBC_INVALID_LICENSE_KEY'		=> 'Your license key is invalid. Enter a valid MaxMind license key.',
	'ACP_FBC_KEEP_STATISTICS'			=> 'Keep statistics',
	'ACP_FBC_KEEP_STATISTICS_EXPLAIN'	=> 'If yes, statistics are kept for the number of allowed and restricted page requests by country code. <em>Warning</em>: these statistics can use a lot of database space. <em>If you set this to no, all statistics are erased.</em>',
	'ACP_FBC_LAST_1_HOURS'				=> 'In Last Hour',
	'ACP_FBC_LAST_12_HOURS'				=> 'In Last 12 Hours',
	'ACP_FBC_LAST_15_MINUTES'			=> 'In Last 15 Minutes',
	'ACP_FBC_LAST_3_HOURS'				=> 'In Last 3 Hours',
	'ACP_FBC_LAST_30_MINUTES'			=> 'In Last 30 Minutes',
	'ACP_FBC_LAST_6_HOURS'				=> 'In Last 6 Hours',
	'ACP_FBC_LAST_DAY'					=> 'In Last 24 Hours',
	'ACP_FBC_LAST_MONTH'				=> 'In Last 30 Days',
	'ACP_FBC_LAST_QUARTER'				=> 'In Last 90 Days',
	'ACP_FBC_LAST_TWO_WEEKS'			=> 'In Last 14 Days',
	'ACP_FBC_LAST_WEEK'					=> 'In Last 7 Days',
	'ACP_FBC_LICENSE_KEY'				=> 'MaxMind license key',
	'ACP_FBC_LICENSE_KEY_EXPLAIN'		=> 'To use MaxMind’s GeoLite2 country code database, you must <a href="https://dev.maxmind.com/geoip/geoip2/geolite2/" target="_blank">acquire a license key</a>. You do <em>not</em> need to purchase a license. Enter the 16 character license key here. You must register on their site to acquire a license key.',
	'ACP_FBC_LOG_ACCESS_ERRORS'			=> 'Log access errors',
	'ACP_FBC_LOG_ACCESS_ERRORS_EXPLAIN'	=> 'If yes, any restricted IPs are logged in the admin log. This can result in very long logs.',
	'ACP_FBC_MAXMIND_ERROR'				=> 'A call to the MaxMind country code database triggered an error. The database is most likely corrupt. You might want to inform the webmaster.',
	'ACP_FBC_NO_LIMIT'					=> 'Use all statistics',
	'ACP_FBC_NO_STATISTICS'				=> 'Statistics are not enabled. Statistics can be enabled from the extension’s settings page.',
	'ACP_FBC_NO_STATISTICS_FOR_RANGE'	=> 'There are no statistics for the range selected.',
	'ACP_FBC_NO_STATISTICS_YET'			=> 'There are no statistics to display yet. This can happen if you just enabled statistics.',
	'ACP_FBC_OPTIONS'					=> '<option value="AF">Afghanistan</option>
<option value="AX">Åland Islands</option>
<option value="AL">Albania</option>
<option value="DZ">Algeria</option>
<option value="AS">American Samoa</option>
<option value="AD">Andorra</option>
<option value="AO">Angola</option>
<option value="AI">Anguilla</option>
<option value="AQ">Antarctica</option>
<option value="AG">Antigua and Barbuda</option>
<option value="AR">Argentina</option>
<option value="AM">Armenia</option>
<option value="AW">Aruba</option>
<option value="AU">Australia</option>
<option value="AT">Austria</option>
<option value="AZ">Azerbaijan</option>
<option value="BS">Bahamas</option>
<option value="BH">Bahrain</option>
<option value="BD">Bangladesh</option>
<option value="BB">Barbados</option>
<option value="BY">Belarus</option>
<option value="BE">Belgium</option>
<option value="BZ">Belize</option>
<option value="BJ">Benin</option>
<option value="BM">Bermuda</option>
<option value="BT">Bhutan</option>
<option value="BO">Bolivia, Plurinational State of</option>
<option value="BQ">Bonaire, Sint Eustatius and Saba</option>
<option value="BA">Bosnia and Herzegovina</option>
<option value="BW">Botswana</option>
<option value="BV">Bouvet Island</option>
<option value="BR">Brazil</option>
<option value="IO">British Indian Ocean Territory</option>
<option value="BN">Brunei Darussalam</option>
<option value="BG">Bulgaria</option>
<option value="BF">Burkina Faso</option>
<option value="BI">Burundi</option>
<option value="KH">Cambodia</option>
<option value="CM">Cameroon</option>
<option value="CA">Canada</option>
<option value="CV">Cape Verde</option>
<option value="KY">Cayman Islands</option>
<option value="CF">Central African Republic</option>
<option value="TD">Chad</option>
<option value="CL">Chile</option>
<option value="CN">China</option>
<option value="CX">Christmas Island</option>
<option value="CC">Cocos (Keeling) Islands</option>
<option value="CO">Colombia</option>
<option value="KM">Comoros</option>
<option value="CG">Congo</option>
<option value="CD">Congo, the Democratic Republic of the</option>
<option value="CK">Cook Islands</option>
<option value="CR">Costa Rica</option>
<option value="CI">Côte d’Ivoire</option>
<option value="HR">Croatia</option>
<option value="CU">Cuba</option>
<option value="CW">Curaçao</option>
<option value="CY">Cyprus</option>
<option value="CZ">Czech Republic</option>
<option value="DK">Denmark</option>
<option value="DJ">Djibouti</option>
<option value="DM">Dominica</option>
<option value="DO">Dominican Republic</option>
<option value="EC">Ecuador</option>
<option value="EG">Egypt</option>
<option value="SV">El Salvador</option>
<option value="GQ">Equatorial Guinea</option>
<option value="ER">Eritrea</option>
<option value="EE">Estonia</option>
<option value="ET">Ethiopia</option>
<option value="FK">Falkland Islands (Malvinas)</option>
<option value="FO">Faroe Islands</option>
<option value="FJ">Fiji</option>
<option value="FI">Finland</option>
<option value="FR">France</option>
<option value="GF">French Guiana</option>
<option value="PF">French Polynesia</option>
<option value="TF">French Southern Territories</option>
<option value="GA">Gabon</option>
<option value="GM">Gambia</option>
<option value="GE">Georgia</option>
<option value="DE">Germany</option>
<option value="GH">Ghana</option>
<option value="GI">Gibraltar</option>
<option value="GR">Greece</option>
<option value="GL">Greenland</option>
<option value="GD">Grenada</option>
<option value="GP">Guadeloupe</option>
<option value="GU">Guam</option>
<option value="GT">Guatemala</option>
<option value="GG">Guernsey</option>
<option value="GN">Guinea</option>
<option value="GW">Guinea-Bissau</option>
<option value="GY">Guyana</option>
<option value="HT">Haiti</option>
<option value="HM">Heard Island and McDonald Islands</option>
<option value="VA">Holy See (Vatican City State)</option>
<option value="HN">Honduras</option>
<option value="HK">Hong Kong</option>
<option value="HU">Hungary</option>
<option value="IS">Iceland</option>
<option value="IN">India</option>
<option value="ID">Indonesia</option>
<option value="IR">Iran, Islamic Republic of</option>
<option value="IQ">Iraq</option>
<option value="IE">Ireland</option>
<option value="IM">Isle of Man</option>
<option value="IL">Israel</option>
<option value="IT">Italy</option>
<option value="JM">Jamaica</option>
<option value="JP">Japan</option>
<option value="JE">Jersey</option>
<option value="JO">Jordan</option>
<option value="KZ">Kazakhstan</option>
<option value="KE">Kenya</option>
<option value="KI">Kiribati</option>
<option value="KP">Korea, Democratic People’s Republic of</option>
<option value="KR">Korea, Republic of</option>
<option value="KW">Kuwait</option>
<option value="KG">Kyrgyzstan</option>
<option value="LA">Lao People’s Democratic Republic</option>
<option value="LV">Latvia</option>
<option value="LB">Lebanon</option>
<option value="LS">Lesotho</option>
<option value="LR">Liberia</option>
<option value="LY">Libya</option>
<option value="LI">Liechtenstein</option>
<option value="LT">Lithuania</option>
<option value="LU">Luxembourg</option>
<option value="MO">Macao</option>
<option value="MK">Macedonia, the former Yugoslav Republic of</option>
<option value="MG">Madagascar</option>
<option value="MW">Malawi</option>
<option value="MY">Malaysia</option>
<option value="MV">Maldives</option>
<option value="ML">Mali</option>
<option value="MT">Malta</option>
<option value="MH">Marshall Islands</option>
<option value="MQ">Martinique</option>
<option value="MR">Mauritania</option>
<option value="MU">Mauritius</option>
<option value="YT">Mayotte</option>
<option value="MX">Mexico</option>
<option value="FM">Micronesia, Federated States of</option>
<option value="MD">Moldova, Republic of</option>
<option value="MC">Monaco</option>
<option value="MN">Mongolia</option>
<option value="ME">Montenegro</option>
<option value="MS">Montserrat</option>
<option value="MA">Morocco</option>
<option value="MZ">Mozambique</option>
<option value="MM">Myanmar</option>
<option value="NA">Namibia</option>
<option value="NR">Nauru</option>
<option value="NP">Nepal</option>
<option value="NL">Netherlands</option>
<option value="NC">New Caledonia</option>
<option value="NZ">New Zealand</option>
<option value="NI">Nicaragua</option>
<option value="NE">Niger</option>
<option value="NG">Nigeria</option>
<option value="NU">Niue</option>
<option value="NF">Norfolk Island</option>
<option value="MP">Northern Mariana Islands</option>
<option value="NO">Norway</option>
<option value="OM">Oman</option>
<option value="PK">Pakistan</option>
<option value="PW">Palau</option>
<option value="PS">Palestinian Territory, Occupied</option>
<option value="PA">Panama</option>
<option value="PG">Papua New Guinea</option>
<option value="PY">Paraguay</option>
<option value="PE">Peru</option>
<option value="PH">Philippines</option>
<option value="PN">Pitcairn</option>
<option value="PL">Poland</option>
<option value="PT">Portugal</option>
<option value="PR">Puerto Rico</option>
<option value="QA">Qatar</option>
<option value="RE">Réunion</option>
<option value="RO">Romania</option>
<option value="RU">Russian Federation</option>
<option value="RW">Rwanda</option>
<option value="BL">Saint Barthélemy</option>
<option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
<option value="KN">Saint Kitts and Nevis</option>
<option value="LC">Saint Lucia</option>
<option value="MF">Saint Martin (French part)</option>
<option value="PM">Saint Pierre and Miquelon</option>
<option value="VC">Saint Vincent and the Grenadines</option>
<option value="WS">Samoa</option>
<option value="SM">San Marino</option>
<option value="ST">Sao Tome and Principe</option>
<option value="SA">Saudi Arabia</option>
<option value="SN">Senegal</option>
<option value="RS">Serbia</option>
<option value="SC">Seychelles</option>
<option value="SL">Sierra Leone</option>
<option value="SG">Singapore</option>
<option value="SX">Sint Maarten (Dutch part)</option>
<option value="SK">Slovakia</option>
<option value="SI">Slovenia</option>
<option value="SB">Solomon Islands</option>
<option value="SO">Somalia</option>
<option value="ZA">South Africa</option>
<option value="GS">South Georgia and the South Sandwich Islands</option>
<option value="SS">South Sudan</option>
<option value="ES">Spain</option>
<option value="LK">Sri Lanka</option>
<option value="SD">Sudan</option>
<option value="SR">Suriname</option>
<option value="SJ">Svalbard and Jan Mayen</option>
<option value="SZ">Swaziland</option>
<option value="SE">Sweden</option>
<option value="CH">Switzerland</option>
<option value="SY">Syrian Arab Republic</option>
<option value="TW">Taiwan, Province of China</option>
<option value="TJ">Tajikistan</option>
<option value="TZ">Tanzania, United Republic of</option>
<option value="TH">Thailand</option>
<option value="TL">Timor-Leste</option>
<option value="TG">Togo</option>
<option value="TK">Tokelau</option>
<option value="TO">Tonga</option>
<option value="TT">Trinidad and Tobago</option>
<option value="TN">Tunisia</option>
<option value="TR">Turkey</option>
<option value="TM">Turkmenistan</option>
<option value="TC">Turks and Caicos Islands</option>
<option value="TV">Tuvalu</option>
<option value="UG">Uganda</option>
<option value="UA">Ukraine</option>
<option value="AE">United Arab Emirates</option>
<option value="GB">United Kingdom</option>
<option value="US">United States</option>
<option value="UM">United States Minor Outlying Islands</option>
<option value="UY">Uruguay</option>
<option value="UZ">Uzbekistan</option>
<option value="VU">Vanuatu</option>
<option value="VE">Venezuela, Bolivarian Republic of</option>
<option value="VN">Viet Nam</option>
<option value="VG">Virgin Islands, British</option>
<option value="VI">Virgin Islands, U.S.</option>
<option value="WF">Wallis and Futuna</option>
<option value="EH">Western Sahara</option>
<option value="YE">Yemen</option>
<option value="ZM">Zambia</option>
<option value="ZW">Zimbabwe</option>',
	'ACP_FBC_OVERRIDE'					=> '(<em>Note</em>: Use of absolute dates will override any relative dates selected. Specify both dates to use absolute dates.)',
	'ACP_FBC_RANGE_EXPLAIN'				=> 'This is a relative range is from now.',
	'ACP_FBC_REQUIREMENTS'				=> 'To install this extension, the store directory must be writable (0777 Unix file permissions). The allow_url_fopen directive must be enabled. The following PHP extensions are required: curl, dom and Phar. This extension works with phpBB 3.2 and 3.3.',
	'ACP_FBC_RESTRICT'					=> 'Restrict',
	'ACP_FBC_RESTRICTED'				=> 'Restricted page requests',
	'ACP_FBC_SETTING_SAVED'				=> 'Settings have been saved successfully!',
	'ACP_FBC_TO'						=> ' to ',
	'ACP_FBC_UNKNOWN'					=> 'Unknown',
	'ACP_FBC_UNSELECT_ALL'				=> 'Unselect all country codes',
));
