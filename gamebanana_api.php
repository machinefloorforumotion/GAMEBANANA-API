<?php

#########################################################
	$api_key                = "YOUR_API_KEY_HERE";
	$debug_mode             = false;
	$path_to_json_include   = "./json.php";
#########################################################

define("GBAPI_API_KEY",$api_key);
define("GBAPI_DEBUG_MODE",$debug_mode);
define("GBAPI_PATH_TO_JSON_INCLUDE",$path_to_json_include);
define("GBAPI_ERROR_MESSAGE_SUFFIX","[GAMEBANANA API] ");

// Check server has what we need
$_aRequiredFunctions = array(
	"ini_get","file_get_contents","strstr","explode","define","preg_replace",
	"substr","count","array_slice","func_get_args");
foreach ($_aRequiredFunctions as $_sFunctionName)
{
	if (GBAPI_DEBUG_MODE && !function_exists($_sFunctionName))
		trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."Required function '$_sFunctionName' missing");
}

if (GBAPI_DEBUG_MODE && @ini_get("allow_url_fopen") == false)
{
	trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."php.ini directive 'allow_url_fopen' must be set to true");
}

if (!function_exists("json_decode"))
{
	if (GBAPI_DEBUG_MODE && !file_exists(GBAPI_PATH_TO_JSON_INCLUDE))
		trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."JSON.php was missing");
	@require_once(GBAPI_PATH_TO_JSON_INCLUDE);
	function json_decode($data)
	{
		$json = new Services_JSON();
		return $json->decode($data);
	}
}

if (!function_exists("json_encode"))
{
	if (GBAPI_DEBUG_MODE && !file_exists(GBAPI_PATH_TO_JSON_INCLUDE))
		trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."JSON.php was missing");
	@require_once(GBAPI_PATH_TO_JSON_INCLUDE);
	function json_encode($data)
	{
		$json = new Services_JSON();
		return $json->encode($data);
	}
}

function api_request($item_type,$itemid,$field)
{
	if (GBAPI_DEBUG_MODE && func_num_args() < 3)
		trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."api_request() requires at least 3 arguments (".func_num_args()." were given)");
	if (GBAPI_DEBUG_MODE && empty($item_type))
		trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."\$item_type in api_request() must be a non-empty string");
	if (GBAPI_DEBUG_MODE && empty($itemid))
		trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."\$itemid in api_request() must be a whole number");
	
	$_oApiRequest = new ApiRequest;
	$_oApiRequest->vSetItemType($item_type);
	$_oApiRequest->vSetItemID($itemid);
	$_aItemFunctions = @array_slice(@func_get_args(),2);
	foreach ($_aItemFunctions as $_sFunction)
	{
		if (GBAPI_DEBUG_MODE && empty($_sFunction))
			trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."\one or more function calls in api_request() were empty");
		$_oApiRequest->vSetFieldToSelect($_sFunction);
	}
	return $_oApiRequest->xGetResult();
}

class ApiRequest
{
	const c_sRequestUrl = "http://gamebanana.com/api";
	
	private $m_sItemType,
			$m_idItemRow,
			$m_aFieldsToSelect = array();
	
	public function vSetItemType($p_sItemType)
	{
		if (GBAPI_DEBUG_MODE && empty($p_sItemType))
			trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."\ItemType must be a non-empty string");
		$this->m_sItemType = $p_sItemType;
	}
	
	public function vSetItemID($p_idItemRow)
	{
		if (GBAPI_DEBUG_MODE && empty($p_idItemRow))
			trigger_error(GBAPI_ERROR_MESSAGE_SUFFIX."\ItemID must be a whole number");
		$this->m_idItemRow = $p_idItemRow;
	}
	
	public function vSetFieldToSelect($p_sFieldToSelect)
	{
		$this->m_aFieldsToSelect[] = $p_sFieldToSelect;
	}
	
	public function xGetResult()
	{
		$_ResultData = @file_get_contents(
			self::c_sRequestUrl."?request=".$this->m_sItemType.
			".".$this->m_idItemRow.
			".".$this->sGetJsonArrayOfItemFunctions().
			"&key=".GBAPI_API_KEY);
		return @json_decode($_ResultData);
	}
	
	private function sGetJsonArrayOfItemFunctions()
	{
		$_aJsonArray = array();
		foreach ($this->m_aFieldsToSelect as $_sItemFunction)
		{
			if (@strstr($_sItemFunction,"."))
			{
				$_aFunctions = @explode(".",$_sItemFunction);
				if (@count($_aFunctions) == 3)
				{
					if (!isset($_aJsonArray[$_aFunctions[0]][$_aFunctions[1]]))
						$_aJsonArray[$_aFunctions[0]][$_aFunctions[1]] = array();
					$_aJsonArray[$_aFunctions[0]][$_aFunctions[1]][] = $_aFunctions[2];
				}
				else // count() == 2
				{
					if (!isset($_aJsonArray[$_aFunctions[0]])) $_aJsonArray[$_aFunctions[0]] = array();
					$_aJsonArray[$_aFunctions[0]][] = $_aFunctions[1];
				}
			}
			else $_aJsonArray[] = $_sItemFunction;
		}
		$_jsonItemFunction = @json_encode($_aJsonArray);
		$_jsonItemFunction = "[".@substr($_jsonItemFunction,1,-1)."]";
		$_jsonItemFunction = @preg_replace('/"[0-9]+":/','',$_jsonItemFunction);
		$_jsonItemFunction = @preg_replace("/(\"[A-Z][a-z]+\(\)\"\:.*\"\])/U",'{$1}',$_jsonItemFunction);
		return $_jsonItemFunction;
	}
}

?>