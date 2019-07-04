<?php
/**
 *
 * Filter by country. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2019, Mark D. Hamill, https://www.phpbbservices.com
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
	'ACP_FBC_ALLOW'						=> 'Toestaan',
	'ACP_FBC_ALLOWED'					=> 'Toegestane paginaverzoeken',
	'ACP_FBC_ALLOW_OUT_OF_COUNTRY_LOGINS'	=> 'Toestaan dat geregistreerde gebruikers aanmelden vanuit landen waarvoor beperkingen gelden',
	'ACP_FBC_ALLOW_OUT_OF_COUNTRY_LOGINS_EXPLAIN'	=> 'Hiermee kunnen gebruikers die uit hun land reizen inloggen. Andere toegang tot het bord is verboden.',
	'ACP_FBC_ALLOW_RESTRICT'			=> 'Sta de geselecteerde landen toe of beperk deze',
	'ACP_FBC_ALLOW_RESTRICT_EXPLAIN'	=> 'Indien ingesteld om toe te staan, zijn alleen verbindingen van oorsprong uit de geselecteerde landen toegestaan. Indien ingesteld om te weigeren, zijn verbindingen van alle landen toegestaan, behalve degene die u selecteert.',
	'ACP_FBC_CHANGE_REPORT_RANGE'		=> 'Wijzig het rapportbereik',
	'ACP_FBC_COUNTRIES'					=> 'Selecteer één of meer landcodes',
	'ACP_FBC_COUNTRIES_EXPLAIN'			=> 'Houd de CTRL toets ingedrukt (Command op een Mac) om meerdere landcodes te selecteren. U kunt een reeks landen selecteren door de SHIFT toets ingedrukt te houden terwijl u de eerste tot de laatste landen in het bereik selecteert. <em>Om te voorkomen dat per ongeluk alle toegang wordt geblokkeerd, zijn alle landen toegestaan, ongeacht de modus, als er geen land is geselecteerd. Als dit is wat u permanent wilt doen, moet u de extensie uitschakelen.</em>',
	'ACP_FBC_COUNTRY_A_Z'				=> 'Land, A-Z',
	'ACP_FBC_COUNTRY_Z_A'				=> 'Land, Z-A',
	'ACP_FBC_COUNTRY_ALLOWED_ASC'		=> 'Toegestane paginaverzoeken, het minste tot het meeste',
	'ACP_FBC_COUNTRY_ALLOWED_DESC'		=> 'Toegestane paginaverzoeken, het meeste tot het minste',
	'ACP_FBC_COUNTRY_NAME'				=> 'Naam van het land',
	'ACP_FBC_COUNTRY_RESTRICTED_ASC'	=> 'Beperkte paginaverzoeken, het minste tot het meeste',
	'ACP_FBC_COUNTRY_RESTRICTED_DESC'	=> 'Beperkte paginaverzoeken, het meeste tot het minste',
	'ACP_FBC_CURRENT_RANGE'				=> 'Bereik van getoonde statistieken',
	'ACP_FBC_DATES_BEGINNING'			=> 'Of kies een absoluut bereik van begindatums',
	'ACP_FBC_DATES_ENDING'				=> 'en einde',
	'ACP_FBC_DENY_ACCESS'				=> 'Uw toegang wordt geweigerd vanwege de landcode die is toegewezen aan uw Internet Protocol (IP) adres. Uw IP is %1$s en uw land is %2$s.',
	'ACP_FBC_DENY_ACCESS_LOGIN'			=> 'Uw toegang wordt geweigerd vanwege de landcode die is toegewezen aan uw Internet Protocol (IP) adres. Uw IP is %1$s en uw land is %2$s. Actieve geregistreerde gebruikers kunnen echter <a href="%3$s">aanmelden</a>.',
	'ACP_FBC_DOWNLOAD_ERROR'			=> 'Kon niet downloaden: %1$s',
	'ACP_FBC_FROM'						=> 'van ',
	'ACP_FBC_IP_NOT_FOUND'				=> 'VPN services toestaan',
	'ACP_FBC_IP_NOT_FOUND_EXPLAIN'		=> 'Als de IP adressen van een gebruiker niet in de database staan, is dit waarschijnlijk van een VPN service (Virteel Privé Netwerk). Als u deze optie inschakelt, kunnen gebruikers gebruikmaken van VPN services, maar kunnen ook spammers toegang krijgen.',
	'ACP_FBC_KEEP_STATISTICS'			=> 'Statistieken bijhouden',
	'ACP_FBC_KEEP_STATISTICS_EXPLAIN'	=> 'Indien ja, worden statistieken bijgehouden voor het aantal toegestane en geweigerde paginaverzoeken per landcode. <em>Waarschuwing</em>: Deze statistieken kunnen veel databaseruimte gebruiken. <em>Als u dit op Nee instelt, worden alle statistieken gewist.</em>',
	'ACP_FBC_LAST_1_HOURS'				=> 'In het laatste uur',
	'ACP_FBC_LAST_12_HOURS'				=> 'In de laatste 12 uren',
	'ACP_FBC_LAST_15_MINUTES'			=> 'In de laatste 15 minuten',
	'ACP_FBC_LAST_3_HOURS'				=> 'In de laatste 3 uren',
	'ACP_FBC_LAST_30_MINUTES'			=> 'In de laatste 30 minuten',
	'ACP_FBC_LAST_6_HOURS'				=> 'In de laatste 6 uren',
	'ACP_FBC_LAST_DAY'					=> 'In de laatste 24 uren',
	'ACP_FBC_LAST_MONTH'				=> 'In de laatste 30 dagen',
	'ACP_FBC_LAST_QUARTER'				=> 'In de laatste 90 dagen',
	'ACP_FBC_LAST_TWO_WEEKS'			=> 'In de laatste 14 dagen',
	'ACP_FBC_LAST_WEEK'					=> 'In de laatste 7 dagen',
	'ACP_FBC_LOG_ACCESS_ERRORS'			=> 'Log toegang fouten',
	'ACP_FBC_LOG_ACCESS_ERRORS_EXPLAIN'	=> 'Zo ja, dan worden eventuele beperkte IP’s in het beheerderslogboek vastgelegd. Dit kan resulteren in zeer lange logboeken.',
	'ACP_FBC_MAXMIND_ERROR'				=> 'Een oproep naar de MaxMind landcodedatabase leidde tot een fout. De database is waarschijnlijk corrupt. U kunt de webmaster hiervan op de hoogte stellen.',
	'ACP_FBC_NO_LIMIT'					=> 'Gebruik alle statistieken',
	'ACP_FBC_NO_STATISTICS'				=> 'Statistieken zijn niet ingeschakeld. Statistieken kunnen worden ingeschakeld via de instellingenpagina voor extensies.',
	'ACP_FBC_NO_STATISTICS_FOR_RANGE'	=> 'Er zijn geen statistieken voor het geselecteerde bereik.',
	'ACP_FBC_NO_STATISTICS_YET'			=> 'Er zijn nog geen statistieken om weer te geven. Dit kan gebeuren als er geen landcodes zijn geselecteerd of u alleen statistieken hebt ingeschakeld.',
	'ACP_FBC_OPTIONS'					=> '<option value="AF">Afghanistan</option>
<option value="AX">Ålandseilanden</option>
<option value="AL">Albanië</option>
<option value="DZ">Algerije</option>
<option value="AS">Amerikaans Samoa</option>
<option value="VI">Amerikaanse Maagdeneilanden</option>
<option value="AD">Andorra</option>
<option value="AO">Angola</option>
<option value="AI">Anguilla</option>
<option value="AQ">Antarctica</option>
<option value="AG">Antigua en Barbuda</option>
<option value="AR">Argentinië</option>
<option value="AM">Armenië</option>
<option value="AW">Aruba</option>
<option value="AU">Australië</option>
<option value="AZ">Azerbeidzjan</option>
<option value="BS">Bahama’s</option>
<option value="BH">Bahrein</option>
<option value="BD">Bangladesh</option>
<option value="BB">Barbados</option>
<option value="BY">Belarus</option>
<option value="BE">België</option>
<option value="BZ">Belize</option>
<option value="BJ">Benin</option>
<option value="BM">Bermuda</option>
<option value="BT">Bhutan</option>
<option value="BO">Bolivia, plurinationale Staat van</option>
<option value="BQ">Bonaire, Sint Eustatius en Saba</option>
<option value="BA">Bosnië en Herzegovina</option>
<option value="BW">Botswana</option>
<option value="BV">Bouveteiland</option>
<option value="BR">Brazilië</option>
<option value="IO">Brits-Indisch oceaan gebied</option>
<option value="VG">Britse Maagdeneilanden</option>
<option value="BN">Brunei Darussalam</option>
<option value="BG">Bulgarije</option>
<option value="BF">Burkina Faso</option>
<option value="BI">Burundi</option>
<option value="KH">Cambodja</option>
<option value="CA">Canada</option>
<option value="CF">Centraal Afrikaanse Republiek</option>
<option value="TD">Chad</option>
<option value="CL">Chili</option>
<option value="CN">China</option>
<option value="CX">Christmaseiland / Kersteiland</option>
<option value="CC">Cocos (Keeling) eilanden</option>
<option value="CO">Colombia</option>
<option value="KM">Comoren</option>
<option value="CG">Congo</option>
<option value="CD">Congo, de Democratische Republiek van de</option>
<option value="CK">Cook Eilanden</option>
<option value="CR">Costa Rica</option>
<option value="CU">Cuba</option>
<option value="CW">Curaçao</option>
<option value="CY">Cyprus</option>
<option value="DK">Denemarken</option>
<option value="DJ">Djibouti</option>
<option value="DM">Dominica</option>
<option value="DO">Dominicaanse Republiek</option>
<option value="DE">Duitsland</option>
<option value="EC">Ecuador</option>
<option value="EG">Egypte</option>
<option value="SV">El Salvador</option>
<option value="GQ">Equatoriaal-Guinea</option>
<option value="ER">Eritrea</option>
<option value="EE">Estland</option>
<option value="SZ">eSwatini</option>
<option value="ET">Ethiopië</option>
<option value="FK">Falklandeilanden (Malvinas)</option>
<option value="FO">Faeröer</option>
<option value="FJ">Fiji</option>
<option value="FI">Finland</option>
<option value="FR">Frankrijk</option>
<option value="GF">Frans-Guyana</option>
<option value="PF">Frans-Polynesië</option>
<option value="TF">Franse zuidelijke gebieden</option>
<option value="GA">Gabon</option>
<option value="GM">Gambia</option>
<option value="GE">Georgië</option>
<option value="GH">Ghana</option>
<option value="GI">Gibraltar</option>
<option value="GR">Griekenland</option>
<option value="GL">Groenland</option>
<option value="GD">Grenada</option>
<option value="GP">Guadeloupe</option>
<option value="GU">Guam</option>
<option value="GT">Guatemala</option>
<option value="GG">Guernsey</option>
<option value="GN">Guinea</option>
<option value="GW">Guinee-Bissau</option>
<option value="GY">Guyana</option>
<option value="HT">Haiti</option>
<option value="HM">Heard-eiland en McDonaldeilanden</option>
<option value="VA">Holy See (Vaticaanstad)</option>
<option value="HN">Honduras</option>
<option value="HK">Hong Kong</option>
<option value="HU">Hongarije</option>
<option value="IS">IJsland</option>
<option value="IN">Indië</option>
<option value="ID">Indonesië</option>
<option value="IR">Iran, Islamitische Republiek</option>
<option value="IQ">Irak</option>
<option value="IE">Ierland</option>
<option value="IM">Isle of Man</option>
<option value="IL">Israël</option>
<option value="IT">Italië</option>
<option value="CI">Ivoorkust</option>
<option value="JM">Jamaica</option>
<option value="JP">Japan</option>
<option value="YE">Jemen</option>
<option value="JE">Jersey</option>
<option value="JO">Jordanië</option>
<option value="KY">Kaaiman Eilanden</option>
<option value="CV">Kaapverdië</option>
<option value="CM">Kameroen</option>
<option value="KZ">Kazakhstan</option>
<option value="KE">Kenia</option>
<option value="KG">Kirgistan</option>
<option value="KI">Kiribati</option>
<option value="KP">Korea, Democratische Volksrepubliek</option>
<option value="KR">Korea, republiek van</option>
<option value="HR">Kroatië</option>
<option value="KW">Koeweit</option>
<option value="LA">Lao Democratische Volksrepubliek</option>
<option value="LV">Letland</option>
<option value="LB">Libanon</option>
<option value="LS">Lesotho</option>
<option value="LR">Liberia</option>
<option value="LY">Libië</option>
<option value="LI">Liechtenstein</option>
<option value="LT">Litouwen</option>
<option value="LU">Luxemburg</option>
<option value="MO">Macao</option>
<option value="MK">Macedonië, de voormalige Joegoslavische Republiek</option>
<option value="MG">Madagascar</option>
<option value="MW">Malawi</option>
<option value="MY">Maleisië</option>
<option value="MV">Maldiven</option>
<option value="ML">Mali</option>
<option value="MT">Malta</option>
<option value="MH">Marshalleilanden</option>
<option value="MQ">Martinique</option>
<option value="MA">Marokko</option>
<option value="MR">Mauritanië</option>
<option value="MU">Mauritius</option>
<option value="YT">Mayotte</option>
<option value="MX">Mexico</option>
<option value="FM">Micronesië, Federale Staten van</option>
<option value="MD">Moldavië, Republiek</option>
<option value="MC">Monaco</option>
<option value="MN">Mongolië</option>
<option value="ME">Montenegro</option>
<option value="MS">Montserrat</option>
<option value="MZ">Mozambique</option>
<option value="MM">Myanmar</option>
<option value="NA">Namibië</option>
<option value="NR">Nauru</option>
<option value="NP">Nepal</option>
<option value="NL">Nederland</option>
<option value="NC">Nieuw-Caledonië</option>
<option value="NZ">Nieuw Zeeland</option>
<option value="NI">Nicaragua</option>
<option value="NE">Niger</option>
<option value="NG">Nigeria</option>
<option value="NU">Niue</option>
<option value="NF">Norfolkeiland</option>
<option value="MP">Noordelijke Marianen</option>
<option value="NO">Noorwegen</option>
<option value="UA">Oekraïne</option>
<option value="UZ">Oezbekistan</option>
<option value="OM">Oman</option>
<option value="TL">Oost-Timor</option>
<option value="AT">Oostenrijk</option>
<option value="PK">Pakistan</option>
<option value="PW">Palau</option>
<option value="PS">Palestijnse territoria</option>
<option value="PA">Panama</option>
<option value="PG">Papoea-Nieuw-Guinea</option>
<option value="PY">Paraguay</option>
<option value="PE">Peru</option>
<option value="PH">Filipijnen</option>
<option value="PN">Pitcairneilanden</option>
<option value="PL">Polen</option>
<option value="PT">Portugal</option>
<option value="PR">Puerto Rico</option>
<option value="QA">Qatar</option>
<option value="RE">Réunion</option>
<option value="RO">Roemenië</option>
<option value="RU">Russische Federatie</option>
<option value="RW">Rwanda</option>
<option value="BL">Saint-Barthélemy</option>
<option value="SH">Sint-Helena, Ascension en Tristan da Cunha</option>
<option value="KN">Saint Kitts en Nevis</option>
<option value="LC">Saint Lucia</option>
<option value="MF">Saint Martin (Frans deel)</option>
<option value="PM">Saint Pierre en Miquelon</option>
<option value="VC">Saint Vincent en de Grenadines</option>
<option value="WS">Samoa</option>
<option value="SM">San Marino</option>
<option value="ST">Sao Tomé en Principe</option>
<option value="SA">Saudi-Arabië</option>
<option value="SN">Senegal</option>
<option value="RS">Servië</option>
<option value="SC">Seychellen</option>
<option value="SL">Sierra Leone</option>
<option value="SG">Singapore</option>
<option value="SX">Sint Maarten (Nederlands deel)</option>
<option value="SK">Slowakije</option>
<option value="SI">Slovenië</option>
<option value="SB">Solomoneilanden</option>
<option value="SO">Somalië</option>
<option value="ES">Spanja</option>
<option value="LK">Sri Lanka</option>
<option value="SD">Sudan</option>
<option value="SR">Suriname</option>
<option value="SJ">Svalbard en Jan Mayen (Spitsbergen)</option>
<option value="SY">Syrische Arabische Republiek</option>
<option value="TW">Taiwan, provincie China</option>
<option value="TJ">Tadzjikistan</option>
<option value="TZ">Tanzania, Verenigde Republiek</option>
<option value="TH">Thailand</option>
<option value="TG">Togo</option>
<option value="TK">Tokelau</option>
<option value="TO">Tonga</option>
<option value="TT">Trinidad en Tobago</option>
<option value="CZ">Tsjechische Republiek</option>
<option value="TN">Tunesië</option>
<option value="TR">Turkije</option>
<option value="TM">Turkmenistan</option>
<option value="TC">Turks- en Caicoseilanden</option>
<option value="TV">Tuvalu</option>
<option value="UG">Uganda</option>
<option value="UY">Uruguay</option>
<option value="VU">Vanuatu</option>
<option value="VE">Venezuela, Bolivariaanse Republiek</option>
<option value="AE">Verenigde Arabische Emiraten</option>
<option value="GB">Verenigd Koninkrijk</option>
<option value="US">Verenigde Staten</option>
<option value="UM">Verenigde Staten, Kleine Afgelegen Eilanden van de</option>
<option value="VN">Vietnam</option>
<option value="WF">Wallis en Futuna</option>
<option value="EH">Westelijke Sahara</option>
<option value="ZM">Zambia</option>
<option value="ZW">Zimbabwe</option>
<option value="ZA">Zuid-Afrika</option>
<option value="GS">Zuid-Georgia en de Zuidelijke Sandwicheilanden</option>
<option value="SS">Zuid-Soedan</option>
<option value="SE">Zweden</option>
<option value="CH">Zwitserland</option>',
	'ACP_FBC_OVERRIDE'					=> '(<em>Merk op</em>: Het gebruik van absolute datums overschrijft geselecteerde relatieve datums. Geef beide datums op om absolute datums te gebruiken.)',
	'ACP_FBC_RANGE_EXPLAIN'				=> 'Dit is een relatief bereik vanaf nu.',
	'ACP_FBC_REQUIREMENTS'				=> 'PHP extensies zijn vereist: curl, dom en Phar. Deze extensie werkt alleen met phpBB 3.2.',
	'ACP_FBC_RESTRICT'					=> 'Weigeren',
	'ACP_FBC_RESTRICTED'				=> 'Geweigerde paginaverzoeken',
	'ACP_FBC_SERIOUS_MAXMIND_ERROR'		=> 'Serious error with MaxMind database used by phpBB Filter by country extension',
	'ACP_FBC_SETTING_SAVED'				=> 'Instellingen zijn succesvol opgeslagen!',
	'ACP_FBC_TO'						=> ' naar ',
	'ACP_FBC_UNKNOWN'					=> 'VPN (onbekend)',
	'ACP_FBC_UNSELECT_ALL'				=> 'Deselecteer alle landcodes',
));
