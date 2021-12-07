<?php

/*
 * This file is part of MailSo.
 *
 * (c) 2014 Usenko Timur
 * (c) 2021 DJMaze
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://datatracker.ietf.org/doc/html/rfc5256
 * https://datatracker.ietf.org/doc/html/rfc5957
 */

namespace MailSo\Imap\Requests;

use MailSo\Imap\Exceptions\RuntimeException;
use MailSo\Log\Enumerations\Type;

/**
 * @category MailSo
 * @package Imap
 */
class SORT extends Request
{
	public
		$sCriterias = 'ALL',
		$sCharset = '',
		$bUid = true,
		$aSortTypes = [],
		$sLimit = '',
		// rfc5267
		$aReturn = [
		/**
		   ALL
			  Return all message numbers/UIDs which match the search criteria,
			  in the requested sort order, using a sequence-set.

		   COUNT
			  As in [ESEARCH].

		   MAX
			  Return the message number/UID of the highest sorted message
			  satisfying the search criteria.

		   MIN
			  Return the message number/UID of the lowest sorted message
			  satisfying the search criteria.

		   PARTIAL 1:500
			  Return all message numbers/UIDs which match the search criteria,
			  in the requested sort order, using a sequence-set.
		 */
		];

	function __construct(\MailSo\Imap\ImapClient $oImapClient)
	{
		if (!$oImapClient->IsSupported('SORT')) {
			$oImapClient->writeLogException(
				new RuntimeException('SORT is not supported'),
				Type::ERROR, true);
		}
		parent::__construct($oImapClient);
	}

	public function SendRequestGetResponse() : \MailSo\Imap\ResponseCollection
	{
		if (!$this->aSortTypes) {
			$this->oImapClient->writeLogException(
				new RuntimeException('SortTypes are missing'),
				Type::ERROR, true);
		}

		$aRequest = array();

		if ($this->aReturn) {
			// RFC 5267 checks
			if (!$this->oImapClient->IsSupported('ESORT')) {
				$this->oImapClient->writeLogException(
					new RuntimeException('ESORT is not supported'),
					Type::ERROR, true);
			}
			if (!$this->oImapClient->IsSupported('CONTEXT=SORT')) {
				foreach ($this->aReturn as $sReturn) {
					if (\preg_match('/PARTIAL|UPDATE|CONTEXT/i', $sReturn)) {
						$this->oImapClient->writeLogException(
							new RuntimeException('CONTEXT=SORT is not supported'),
							Type::ERROR, true);
					}
				}
			}
			$aRequest[] = 'RETURN';
			$aRequest[] = $this->aReturn;
		}

		$aRequest[] = $this->aSortTypes;

		$sSearchCriterias = (\strlen($this->sCriterias) && '*' !== $this->sCriterias) ? $this->sCriterias : 'ALL';

		if (!$this->sCharset) {
			$this->sCharset = \MailSo\Base\Utils::IsAscii($sSearchCriterias) ? 'US-ASCII' : 'UTF-8';
		}

		$aRequest[] = \strtoupper($this->sCharset);
		$aRequest[] = $sSearchCriterias;

		if (\strlen($this->sLimit)) {
			$aRequest[] = $this->sLimit;
		}

		return $this->oImapClient->SendRequestGetResponse(
			($this->bUid ? 'UID SORT' : 'SORT'),
			$aRequest
		);
	}
}
