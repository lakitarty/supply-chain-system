UPDATE inventory_locations
SET is_default = IF(location_type = 'SHOP_FLOOR', 1, 0);