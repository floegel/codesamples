<?php
/**
 *
 * Calculate the invoices values (subtotal, total, discount values and vat values)
 *
 * @param InvoiceBundle_Models_Invoice $invoice
 * @return array contains the keys total, subtotal, discount and vats. see example
 * @example if the invoice has two invoice items:
 * [1] vat 7%, sum 100eur
 * [2] vat 19%, sum 100eur
 * and a discount of 10%, the returned array will look like this:
 * array(4) {
 * 	 ["subtotal"]=> float(200)
 * 	 ["total"]=> float(203,4)
 * 	 ["discount"]=> array(3) { ["perc"]=> int(10) ["of"]=> float(200) ["total"]=> float(20) }
 * 	 ["vats"]=> array(2) {
 * 	   [7]=> array(2) { ["of"]=> float(90) ["total"]=> float(6,3) }
 * 	   [19]=> array(2) { ["of"]=> float(90) ["total"]=> float(17,1) }
 * 	 }
 * }
 */
public function calculateInvoiceValues(InvoiceBundle_Models_Invoice $invoice)
{
	$invoiceItems = $invoice->getItems();
	$discountPerc = intval($invoice->getDiscount()); // discount in %

	$subtotal = 0;
	$total = 0;
	$vatTotal = 0;
	$vatList = array();
	$discountTotal = 0;

	foreach ($invoiceItems as $item)
	{
		$finalPrice = ($item->getUnits() * $item->getPrice());
		$vatPerc = intval($item->getVat());

		$subtotal += $finalPrice;

		if ($discountPerc > 0)
		{
			$partialDiscount = ($finalPrice * $discountPerc / 100);
			$discountTotal += $partialDiscount;
			$finalPrice -= $partialDiscount;
		}

		$partialVat = ($finalPrice * $vatPerc / 100);
		$vatTotal += $partialVat;

		if ($vatPerc > 0)
		{
			if (!isset($vatList[$vatPerc]))
			{
				$vatList[$vatPerc] = array("of" => 0, "total" => 0);
			}
			$vatList[$vatPerc]["of"] += $finalPrice;
			$vatList[$vatPerc]["total"] += $partialVat;
		}
	}

	$result = array(
		"subtotal" => $subtotal,
		"total" => ($subtotal - $discountTotal + $vatTotal),
		"discount" => array("perc" => $discountPerc, "of" => $subtotal, "total" => $discountTotal),
		"vats" => $vatList
	);
	return $result;
}