pageTracker._addTrans(
	"<?= $txn_id; ?>", // Order ID
	"", // Affiliation
	"<?= $mc_gross; ?>", // Total
	"<?= $tax; ?>", // Tax
	"<?= $shipping; ?>", // Shipping
	"<?= $address_city; ?>", // City
	"<?= $address_state; ?>", // State
	"<?= $address_country; ?>" // Country
);

pageTracker._addItem(
	"<?= $txn_id ?>", // Order ID
	"<?= $item_number ?>", // SKU
	"<?= $item_name ?>", // Product Name
	"", // Category
	"<?= $item_number ?>", // Price
	"<?= $quantity ?>" // Quantity
);

pageTracker._trackTrans();