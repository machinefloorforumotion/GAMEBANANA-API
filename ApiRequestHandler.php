<?php

/*
	README!
	
	This script is provided as an insight into the way
	GAMEBANANA handles API requests for curious developers.
	It's not necessary to understand this script or to
	include it in any of your API projects!
*/

// This is how your income request is handled
$_oApi = new ApiRequestHandler;
$_oApi->vSetIpAddress($_SERVER["REMOTE_ADDR"]);
$_oApi->vSetUserAgent($_SERVER["HTTP_USER_AGENT"]);
$_oApi->vSetApiKey($_GET["key"]);
$_oApi->vSetDataCall($_GET["request"]);
echo $_oApi->jsonGetResponse();

class ApiRequestHandler
{
	private $m_aAllowedItemTypes = array("Member"),
			$m_aRequestedSubfunctionPaths,
			$m_aUnrecognizedSubfunctions,
			$m_sDataCall,
			$m_aItemFunction,
			$m_aAllowedSubfunctionPaths = array
			(
				"Member" => array
				(
					"name",
					"user_title",
					"Ban().bIsBanned()",
					"Buddies().List().aGetBuddyRowIds()",
					"Buddies().List().aGetOnlineBuddyRowIds()",
					"Buddies().Count().bHasBuddies()",
					"Buddies().Count().bHasOnlineBuddies()",
					"Buddies().Count().nGetOnlineBuddiesCount()",
					"Buddies().Count().nGetBuddiesCount()",
					"ContactInfo().aGetContactInfo()",
					"Guild().bMemberIsInAnyGuild()",
					"Levels().aGetPointsLevelInfo()",
					"Levels().aGetEfCountLevelInfo()",
					"Levels().aGetAgeLevelInfo()",
					"Levels().aGetClearanceLevelInfo()",
					"Medals().bHasMedals()",
					"Medals().aGetMedals()",
					"Modgroup().bIsInAnyModgroup()",
					"Modgroup().aGetModgroupsMemberIsPartOf()",
					"Modgroup().bIsModerator()",
					"Modgroup().bIsSuperModerator()",
					"Modgroup().bIsAdmin()",
					"Modgroup().bIsSuperAdmin()",
					"Modgroup().aGetModgroupsMemberIsNotPartOf()",
					"OnlineStatus().bIsOnline()",
					"OnlineStatus().sGetLocation()",
					"OnlineStatus().tsGetLastSeenTime()",
					"OnlineStatus().tsGetSessionCreationTime()",
					"PcSpecs().aGetPcSpecs()",
					"Ripe().bHasRipe()",
					"SoftwareKit().aGetSoftwareKit()",
					"Url().sGetActivationUrl()",
					"Url().sGetReactivationUrl()",
					"Url().sGetResetPasswordUrl()",
					"Url().sGetLoginUrl()",
					"Url().sGetItemBaseUrl()",
					"Url().sGetAvatarUrl()",
					"Url().sGetUpicUrl()",
					"Url().sGetSigUrl()",
					"Url().sGetSubscribersUrl()",
					"Url().sGetPointsLogUrl()",
					"Url().sGetStampsLogUrl()",
					"Url().sGetSettingsUrl()",
					"Url().sGetBuddiesUrl()",
					"Url().sGetBuddyRequestsUrl()",
					"Url().sGetMedalsUrl()",
				),
			);
	
	public function vSetIpAddress()
	{
		// to monitor usage - not used yet
	}
	
	public function vSetUserAgent()
	{
		// to monitor usage - not used yet
	}
	
	public function vSetApiKey()
	{
		// to monitor usage - not used yet
	}
	
	public function vSetDataCall($p_sDataCall)
	{
		$this->m_sDataCall = trim($p_sDataCall);
	}
	
	public function jsonGetResponse()
	{
		$_aDataCallBits = explode(".",$this->m_sDataCall,3);
		if (count($_aDataCallBits) > 2) list($this->m_sItemType,$this->m_idItemRow,$this->m_jsonItemFunction) = $_aDataCallBits;
		else list($this->m_sItemType,$this->m_jsonItemFunction) = $_aDataCallBits;
		
		if (empty($this->m_sItemType)) return array("_sError"=>'ItemType required');
		elseif (!$this->bItemTypeIsValid()) return array("_sError"=>'Unrecognized ItemType `'.$this->m_sItemType.'`');
		elseif (!$this->bItemRowIdIsValid()) return array("_sError"=>'ItemID `'.$this->m_idItemRow.'` must be a number greater than 0');
		elseif (!$this->bItemRowIdExists()) return array("_sError"=>'ItemID `'.$this->m_idItemRow.'` doesn\'t exist');
		elseif (empty($this->m_jsonItemFunction)) return array("_sError"=>'ItemFunction required');
		elseif (!$this->bJsonIsValid()) return array("_sError"=>'ItemFunction `'.$this->m_jsonItemFunction.'` was malformed');
		elseif (!$this->bMethodsAreAllowed())
		{
			if (count($this->m_aUnrecognizedSubfunctions) > 1)
			{
				$_sLastSubfunction = array_pop($this->m_aUnrecognizedSubfunctions);
				return array("_sError"=>'Unrecognized subfunctions `'.implode("`, `",$this->m_aUnrecognizedSubfunctions).'` and `'.$_sLastSubfunction.'` in ItemFunction');
			}
			else return array("_sError"=>'Unrecognized subfunction `'.reset($this->m_aUnrecognizedSubfunctions).'` in ItemFunction');
		}
		elseif ($this->bMethodCallCountExceedsMaximum()) return array("_sError"=>'ItemFunction subfunction count ('.count($this->m_aRequestedSubfunctionPaths).') exceed the maximum (10)');
		$_oDataPortal = new DataGetter;
		$_oDataPortal->vSetCall($this->m_sDataCall);
		return json_encode(array($_oDataPortal->Get()));
	}
	
	private function bItemTypeIsValid()
	{
		return (in_array($this->m_sItemType,$this->m_aAllowedItemTypes));
	}
	
	private function bItemRowIdIsValid()
	{
		if (is_null($this->m_idItemRow)) return true;
		return (strval(intval($this->m_idItemRow)) === strval($this->m_idItemRow) || intval($this->m_idItemRow) > 0);
	}
	
	private function bItemRowIdExists()
	{
		if (is_null($this->m_idItemRow)) return true;
		$_oRowExists = new RowExists;
		$_oRowExists->vSetTableName(model2table($this->m_sItemType));
		$_oRowExists->vSetRowId($this->m_idItemRow);
		return $_oRowExists->bRowExists();
	}
	
	private function bJsonIsValid()
	{
		$this->m_aItemFunction= json_decode($this->m_jsonItemFunction,true);
		return (!is_null($this->m_aItemFunction) && is_array($this->m_aItemFunction) && !empty($this->m_aItemFunction));
	}
	
	private function bMethodsAreAllowed()
	{
		$this->m_aRequestedSubfunctionPaths = $this->aGetFullSubfunctionPaths($this->m_aItemFunction);
		$this->m_aUnrecognizedSubfunctions = array_diff($this->m_aRequestedSubfunctionPaths,$this->m_aAllowedSubfunctionPaths[$this->m_sItemType]);
		return (empty($this->m_aUnrecognizedSubfunctions));
	}
	
	private function bMethodCallCountExceedsMaximum()
	{
		return (count($this->m_aRequestedSubfunctionPaths) > 10);
	}
	
	private function aGetFullSubfunctionPaths($p_aSubfunctions)
	{
		if (!function_exists("vTraverseArray"))
		{
			function vTraverseArray($p_aFullSubfunctionPaths,$p_sParentSubfunction,$p_oIterator)
			{
				while ($p_oIterator->valid())
				{
					if ($p_oIterator->hasChildren())
					{
						vTraverseArray(
							&$p_aFullSubfunctionPaths,
							(preg_match("/[0-9]+/",$p_oIterator->key())?$p_sParentSubfunction:$p_sParentSubfunction.".".$p_oIterator->key()),
							$p_oIterator->getChildren());
					}
					else $p_aFullSubfunctionPaths[] = substr($p_sParentSubfunction.".".$p_oIterator->current(),1);
					$p_oIterator->next();
				}
			}
		}
		$_oInterator = new RecursiveArrayIterator($p_aSubfunctions);
		$_aFullSubfunctionPaths = array();
		iterator_apply($_oInterator,'vTraverseArray',array(&$_aFullSubfunctionPaths,"",$_oInterator));
		return $_aFullSubfunctionPaths;
	}
}

?>