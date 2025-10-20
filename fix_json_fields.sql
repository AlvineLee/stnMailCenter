-- =====================================================
-- JSON 필드를 별도 컬럼으로 분리하는 스키마 개선
-- =====================================================

-- 1. tbl_orders_quick 테이블에 모든 필드 추가
ALTER TABLE tbl_orders_quick 
ADD COLUMN departure_address VARCHAR(255) DEFAULT '',
ADD COLUMN destination_address VARCHAR(255) DEFAULT '',
ADD COLUMN delivery_instructions TEXT DEFAULT '',
ADD COLUMN delivery_route VARCHAR(50) DEFAULT '',
ADD COLUMN box_selection VARCHAR(50) DEFAULT '',
ADD COLUMN box_quantity INT DEFAULT 0,
ADD COLUMN pouch_selection VARCHAR(50) DEFAULT '',
ADD COLUMN pouch_quantity INT DEFAULT 0,
ADD COLUMN shopping_bag_selection VARCHAR(50) DEFAULT '';

-- 2. 기존 JSON 데이터를 새 컬럼으로 마이그레이션
UPDATE tbl_orders_quick 
SET 
    departure_address = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.departure_address')),
    destination_address = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.destination_address')),
    delivery_instructions = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.delivery_instructions')),
    delivery_route = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.delivery_route')),
    box_selection = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.package_info.box_selection')),
    box_quantity = CAST(JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.package_info.box_quantity')) AS UNSIGNED),
    pouch_selection = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.package_info.pouch_selection')),
    pouch_quantity = CAST(JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.package_info.pouch_quantity')) AS UNSIGNED),
    shopping_bag_selection = JSON_UNQUOTE(JSON_EXTRACT(special_instructions, '$.package_info.shopping_bag_selection'))
WHERE special_instructions IS NOT NULL 
AND special_instructions != '';

-- 3. 기존 special_instructions 컬럼은 나중에 제거 가능
-- ALTER TABLE tbl_orders_quick DROP COLUMN special_instructions;

-- 4. 확인 쿼리
SELECT 
    order_id,
    delivery_method,
    urgency_level,
    departure_address,
    destination_address,
    delivery_instructions,
    delivery_route,
    box_selection,
    box_quantity,
    pouch_selection,
    pouch_quantity,
    shopping_bag_selection,
    special_instructions
FROM tbl_orders_quick;
