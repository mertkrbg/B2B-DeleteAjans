<?php declare(strict_types=1);

namespace Database\Seeds;

require_once __DIR__ . '/ProductImageGenerator.php';

use PDO;

return static function (PDO $pdo): void {
    $now = date('Y-m-d H:i:s');
    $catalog = require __DIR__ . '/data_catalog.php';
    $companyData = require __DIR__ . '/data_companies.php';
    $txData = require __DIR__ . '/data_transactions.php';

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['rma_requests','notifications','audit_logs','payment_notifications','current_account_transactions','quote_items','quotes','order_status_history','order_items','orders','cart_items','carts','product_tier_prices','price_list_items','product_stocks','product_images','products','price_lists','currency_rates','categories','brands','warehouses','login_attempts','password_resets','users','company_addresses','companies','system_settings'] as $t) {
        $pdo->exec(sprintf('TRUNCATE TABLE `%s`', $t));
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $insert = static function (string $table, array $data) use ($pdo): int {
        $cols = array_keys($data);
        $ph = array_map(static fn ($c) => ':' . $c, $cols);
        $stmt = $pdo->prepare(sprintf('INSERT INTO `%s` (`%s`) VALUES (%s)', $table, implode('`, `', $cols), implode(',', $ph)));
        foreach ($data as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    };
    $slugify = static function (string $t): string {
        $t = strtr($t, ['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u','Ç'=>'c','Ğ'=>'g','İ'=>'i','Ö'=>'o','Ş'=>'s','Ü'=>'u']);
        $t = strtolower($t);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $t) ?? '', '-');
    };
    $parseSpecs = static function (string $packed): array {
        $out = [];
        foreach (explode('|', $packed) as $pair) {
            $p = explode(':', $pair, 2);
            if (count($p) === 2) { $out[trim($p[0])] = trim($p[1]); }
        }
        return $out;
    };

    $warehouseIds = [];
    foreach ($catalog['warehouses'] as $w) {
        $warehouseIds[$w['code']] = $insert('warehouses', $w + ['is_active' => 1]);
    }

    $categoryIds = [];
    $sort = 0;
    foreach ($catalog['categories'] as $c) {
        $sort += 10;
        $categoryIds[$c['key']] = $insert('categories', [
            'parent_id' => $c['parent'] !== null ? ($categoryIds[$c['parent']] ?? null) : null,
            'name' => $c['name'], 'slug' => $c['key'], 'icon' => $c['icon'],
            'sort_order' => $sort, 'is_active' => 1, 'created_at' => $now,
        ]);
    }

    $brandIds = [];
    foreach ($catalog['brands'] as $b) {
        $brandIds[$b] = $insert('brands', ['name'=>$b,'slug'=>$slugify($b),'is_active'=>1,'created_at'=>$now]);
    }

    $priceListIds = [];
    foreach ($catalog['price_lists'] as $pl) {
        $priceListIds[$pl['code']] = $insert('price_lists', [
            'code'=>$pl['code'],'name'=>$pl['name'],'currency'=>'TRY','discount_modifier'=>$pl['modifier'],
            'valid_from'=>date('Y-m-d'),'valid_until'=>date('Y-m-d', strtotime('+1 year')),
            'is_active'=>1,'created_at'=>$now,
        ]);
    }

    foreach ($catalog['currency_rates'] as $cr) {
        $insert('currency_rates', ['currency_code'=>$cr['code'],'buying_rate'=>$cr['buying'],'selling_rate'=>$cr['selling']]);
    }

    $imageDir = BASE_PATH . '/public/assets/images/products';
    $productIds = [];
    $productInfo = [];
    $pi = 0;
    foreach ($catalog['products'] as $p) {
        [$sku,$name,$cat,$brand,$price,$unit,$box,$min,$step,$desi,$featured,$desc,$specPacked,$currency,$stock] = $p;
        $fileName = strtolower($sku) . '.png';
        ProductImageGenerator::generate($sku, $name, $cat, $imageDir . '/' . $fileName, $brand);
        $idx = (int) substr($sku, -4);
        $id = $insert('products', [
            'category_id'=>$categoryIds[$cat],'brand_id'=>$brandIds[$brand],'sku'=>$sku,
            'barcode'=>'868'.str_pad((string)$idx,10,'0',STR_PAD_LEFT),'oem_code'=>'OEM-'.$idx,
            'name'=>$name,'slug'=>$slugify($name).'-'.strtolower(substr($sku,4)),'short_description'=>$desc,
            'technical_specs_json'=>json_encode($parseSpecs($specPacked), JSON_UNESCAPED_UNICODE),
            'unit_type'=>$unit,'box_quantity'=>$box,'min_order_qty'=>$min,'order_step_qty'=>$step,
            'base_list_price'=>$price,'currency'=>$currency,'vat_rate'=>20.00,'desi'=>$desi,
            'main_image'=>'images/products/'.$fileName,'is_featured'=>$featured,'is_active'=>1,
            'created_at'=>$now,'updated_at'=>$now,
        ]);
        $productIds[$sku] = $id;
        $productInfo[$sku] = ['price'=>(float)$price,'currency'=>$currency,'name'=>$name,'vat'=>20.0,'cat'=>$cat];
        $insert('product_images', ['product_id'=>$id,'image_path'=>'images/products/'.$fileName,'alt_text'=>$name,'sort_order'=>0,'is_primary'=>1]);
        $wi = 0;
        foreach ($warehouseIds as $wid) {
            $phys = $stock[$wi] ?? 0;
            $insert('product_stocks', [
                'product_id'=>$id,'warehouse_id'=>$wid,'physical_qty'=>$phys,
                'reserved_qty'=>(int)floor($phys*0.08),'incoming_qty'=>($wi===0?50:0),
                'expected_date'=>($wi===0?date('Y-m-d',strtotime('+12 days')):null),
            ]);
            $wi++;
        }
        foreach ([[max($min,10),49,$featured?3.0:2.0],[50,99,$featured?6.0:4.5],[100,null,$featured?10.0:8.0]] as [$tmin,$tmax,$dr]) {
            $insert('product_tier_prices', ['product_id'=>$id,'min_qty'=>$tmin,'max_qty'=>$tmax,'discount_rate'=>$dr]);
        }
        if ($pi % 2 === 0) {
            $insert('price_list_items', ['price_list_id'=>$priceListIds['PL-GOLD'],'product_id'=>$id,'special_price'=>round($price*0.94,4),'extra_discount_rate'=>0.00]);
        }
        if ($pi % 3 === 0) {
            $insert('price_list_items', ['price_list_id'=>$priceListIds['PL-VIP'],'product_id'=>$id,'special_price'=>round($price*0.88,4),'extra_discount_rate'=>0.00]);
        }
        $pi++;
    }

    $companies = [];
    foreach ($companyData['companies'] as $key => $c) {
        $id = $insert('companies', [
            'company_code'=>$c['code'],'title'=>$c['title'],'brand_name'=>$c['brand'],
            'tax_office'=>$c['tax_office'],'tax_number'=>$c['tax_number'],
            'mersis_no'=>'MRS'.str_pad($c['tax_number'],13,'0',STR_PAD_LEFT),
            'tier_level'=>$c['tier'],'price_list_id'=>($c['price_list']?$priceListIds[$c['price_list']]:null),
            'base_discount_rate'=>$c['discount'],'credit_limit'=>$c['credit'],'current_balance'=>$c['balance'],
            'overdue_balance'=>$c['overdue'],'payment_term_days'=>$c['term'],
            'default_warehouse_id'=>$warehouseIds['IST-01'],'status'=>$c['status'],
            'phone'=>'0212 000 '.substr($c['tax_number'],-4),'email'=>$c['email'],
            'city'=>$c['city'],'district'=>$c['district'],
            'address'=>$c['brand'].' Merkez, '.$c['district'].' / '.$c['city'],
            'created_at'=>$now,'updated_at'=>$now,
        ]);
        $addressId = $insert('company_addresses', [
            'company_id'=>$id,'address_type'=>'both','title'=>$c['brand'].' Merkez Depo',
            'contact_name'=>$c['brand'].' Yetkilisi','contact_phone'=>'0212 000 '.substr($c['tax_number'],-4),
            'city'=>$c['city'],'district'=>$c['district'],
            'full_address'=>$c['brand'].' Merkez, '.$c['district'].' / '.$c['city'],'is_default'=>1,
        ]);
        $companies[$key] = ['id'=>$id,'address_id'=>$addressId,'discount'=>(float)$c['discount'],'term'=>(int)$c['term'],'sales_rep'=>$c['sales_rep'],'city'=>$c['city'],'district'=>$c['district'],'brand'=>$c['brand']];
    }

    $userIds = [];
    foreach ($companyData['users'] as $u) {
        $userIds[$u['email']] = $insert('users', [
            'company_id'=>$u['company'] !== null ? $companies[$u['company']]['id'] : null,
            'full_name'=>$u['name'],'email'=>$u['email'],'phone'=>'0555 000 0000',
            'password_hash'=>password_hash($u['password'], PASSWORD_BCRYPT, ['cost'=>12]),
            'role'=>$u['role'],'order_approval_limit'=>$u['limit'],'can_view_prices'=>$u['vp'],
            'can_view_finance'=>$u['vf'],'is_active'=>1,'created_at'=>$now,'updated_at'=>$now,
        ]);
    }
    foreach ($companies as $key => $info) {
        if ($info['sales_rep'] !== null && isset($userIds[$info['sales_rep']])) {
            $pdo->prepare('UPDATE companies SET sales_rep_id = :uid WHERE id = :id')
                ->execute([':uid'=>$userIds[$info['sales_rep']],':id'=>$info['id']]);
        }
    }

    $rate = static function (string $currency) use ($catalog): float {
        foreach ($catalog['currency_rates'] as $cr) {
            if ($cr['code'] === $currency) { return (float)$cr['selling']; }
        }
        return 1.0;
    };
    $netOf = static function (string $sku, int $qty, float $disc) use ($productInfo, $rate): array {
        $info = $productInfo[$sku];
        $list = $info['price'] * $rate($info['currency']);
        $unitNet = round($list * (1 - $disc / 100), 4);
        $lineNet = round($unitNet * $qty, 4);
        $lineVat = round($lineNet * $info['vat'] / 100, 4);
        return ['list'=>$list,'unit_net'=>$unitNet,'line_net'=>$lineNet,'line_vat'=>$lineVat,'meta'=>$info];
    };

    foreach ($txData['carts'] as $cart) {
        $cid = $companies[$cart['company']]['id'];
        $uid = $userIds[$cart['user']];
        $cartId = $insert('carts', ['company_id'=>$cid,'user_id'=>$uid,'cart_name'=>$cart['name'],'is_template'=>$cart['is_template'],'created_at'=>$now,'updated_at'=>$now]);
        foreach ($cart['items'] as [$sku,$qty]) {
            $calc = $netOf($sku, $qty, $companies[$cart['company']]['discount']);
            $insert('cart_items', [
                'cart_id'=>$cartId,'product_id'=>$productIds[$sku],'warehouse_id'=>$warehouseIds['IST-01'],
                'quantity'=>$qty,'unit_list_price'=>$calc['list'],'applied_discount_rate'=>$companies[$cart['company']]['discount'],
                'unit_net_price'=>$calc['unit_net'],'vat_rate'=>$calc['meta']['vat'],'line_total_net'=>$calc['line_net'],
            ]);
        }
    }

    $orderNo = 0;
    foreach ($txData['orders'] as $o) {
        $orderNo++;
        $orderNumber = sprintf('SIP-%s-%05d', date('Y'), $orderNo);
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . (int)$o['days_ago'] . ' days'));
        $cid = $companies[$o['company']]['id'];
        $disc = $companies[$o['company']]['discount'];
        $subtotalList = 0.0; $subtotalNet = 0.0; $totalVat = 0.0; $lines = [];
        foreach ($o['items'] as [$sku,$qty]) {
            $calc = $netOf($sku, $qty, $disc);
            $subtotalList += $calc['list'] * $qty;
            $subtotalNet += $calc['line_net'];
            $totalVat += $calc['line_vat'];
            $lines[] = [$sku,$qty,$calc];
        }
        $grand = round($subtotalNet + $totalVat, 4);
        $orderId = $insert('orders', [
            'order_number'=>$orderNumber,'company_id'=>$cid,'created_by_user_id'=>$userIds[$o['buyer']],
            'shipping_address_id'=>$companies[$o['company']]['address_id'],'billing_address_id'=>$companies[$o['company']]['address_id'],
            'customer_po_number'=>$o['po'],'payment_method'=>$o['method'],'payment_term_days'=>$o['term'],
            'status'=>$o['status'],'subtotal_list'=>round($subtotalList,4),'total_discount'=>round($subtotalList-$subtotalNet,4),
            'subtotal_net'=>round($subtotalNet,4),'shipping_cost'=>0,'total_vat'=>round($totalVat,4),'grand_total'=>$grand,
            'cargo_company'=>$o['cargo'],'tracking_number'=>$o['tracking'],'invoice_number'=>$o['invoice'],
            'created_at'=>$createdAt,'updated_at'=>$createdAt,
        ]);
        foreach ($lines as [$sku,$qty,$calc]) {
            $insert('order_items', [
                'order_id'=>$orderId,'product_id'=>$productIds[$sku],'warehouse_id'=>$warehouseIds['IST-01'],
                'sku_snapshot'=>$sku,'name_snapshot'=>$calc['meta']['name'],'quantity'=>$qty,'shipped_quantity'=>$o['status']==='delivered'?$qty:0,
                'unit_list_price'=>$calc['list'],'discount_rate'=>$disc,'unit_net_price'=>$calc['unit_net'],
                'vat_rate'=>$calc['meta']['vat'],'line_vat'=>$calc['line_vat'],'line_total'=>$calc['line_net'],
            ]);
        }
        $insert('order_status_history', ['order_id'=>$orderId,'user_id'=>$userIds[$o['buyer']],'old_status'=>null,'new_status'=>'pending_company_approval','comment'=>'Sipariş oluşturuldu.','created_at'=>$createdAt]);
        if ($o['status'] !== 'pending_company_approval') {
            $insert('order_status_history', ['order_id'=>$orderId,'user_id'=>$userIds['satis@b2b.test'],'old_status'=>'pending_company_approval','new_status'=>$o['status'],'comment'=>'Sipariş durumu güncellendi.','created_at'=>$createdAt]);
        }
    }

    $quoteNo = 0;
    foreach ($txData['quotes'] as $q) {
        $quoteNo++;
        $quoteNumber = sprintf('TKL-%s-%04d', date('Y'), $quoteNo);
        $disc = $companies[$q['company']]['discount'];
        $offeredTotal = 0.0; $hasOffer = false; $lines = [];
        foreach ($q['items'] as [$sku,$qty,$requested,$offered]) {
            $calc = $netOf($sku, $qty, $disc);
            $offeredTotal += ($offered !== null ? $offered * $qty : 0);
            if ($offered !== null) { $hasOffer = true; }
            $lines[] = [$sku,$qty,$calc,$requested,$offered];
        }
        $quoteId = $insert('quotes', [
            'quote_number'=>$quoteNumber,'company_id'=>$companies[$q['company']]['id'],'user_id'=>$userIds[$q['user']],
            'status'=>$q['status'],'valid_until'=>date('Y-m-d', strtotime('+' . (int)$q['valid_days'] . ' days')),
            'target_total'=>null,'offered_total'=>$hasOffer?round($offeredTotal,4):null,
            'customer_note'=>$q['customer_note'],'admin_note'=>$q['admin_note'],'created_at'=>$now,'updated_at'=>$now,
        ]);
        foreach ($lines as [$sku,$qty,$calc,$requested,$offered]) {
            $insert('quote_items', [
                'quote_id'=>$quoteId,'product_id'=>$productIds[$sku],'quantity'=>$qty,
                'current_net_price'=>$calc['unit_net'],'requested_price'=>$requested,'offered_price'=>$offered,
                'offered_discount_rate'=>($offered !== null && $calc['list'] > 0) ? round((1 - $offered / $calc['list']) * 100, 2) : null,
            ]);
        }
    }

    $txNo = 0;
    foreach ($txData['current_accounts'] as $companyKey => $rows) {
        $running = 0.0;
        foreach ($rows as [$type,$desc,$debit,$credit,$daysAgo,$dueDays,$refType]) {
            $txNo++;
            $running += (float)$debit - (float)$credit;
            $createdAt = date('Y-m-d H:i:s', strtotime('-' . (int)$daysAgo . ' days'));
            $due = $dueDays !== null ? date('Y-m-d', strtotime($createdAt . ' ' . (int)$dueDays . ' days')) : null;
            $insert('current_account_transactions', [
                'company_id'=>$companies[$companyKey]['id'],'transaction_no'=>sprintf('CT-%s-%05d', date('Y'), $txNo),
                'type'=>$type,'reference_type'=>$refType,'description'=>$desc,'debit_amount'=>$debit,'credit_amount'=>$credit,
                'running_balance'=>round($running,4),'due_date'=>$due,'status'=>'confirmed',
                'created_by'=>$userIds['finans@b2b.test'],'created_at'=>$createdAt,
            ]);
        }
    }

    foreach ($txData['payment_notifications'] as $pn) {
        $insert('payment_notifications', [
            'company_id'=>$companies[$pn['company']]['id'],'amount'=>$pn['amount'],'payment_method'=>$pn['method'],
            'bank_name'=>$pn['bank'],'reference_no'=>$pn['ref'],'note'=>$pn['note'],'status'=>$pn['status'],
            'created_at'=>date('Y-m-d H:i:s', strtotime('-' . (int)$pn['days_ago'] . ' days')),
        ]);
    }

    foreach ($txData['notifications'] as $n) {
        $insert('notifications', [
            'company_id'=>$companies[$n['company']]['id'],'user_id'=>$userIds[$n['user']],
            'title'=>$n['title'],'message'=>$n['message'],'type'=>$n['type'],'is_read'=>0,
            'created_at'=>date('Y-m-d H:i:s', strtotime('-' . (int)$n['days_ago'] . ' days')),
        ]);
    }

    foreach ($txData['audit_logs'] as $a) {
        $insert('audit_logs', [
            'user_id'=>$userIds[$a['user']],'action'=>$a['action'],'entity_type'=>$a['entity'],
            'entity_id'=>$a['entity_id'],'new_values_json'=>json_encode(['note'=>$a['desc']], JSON_UNESCAPED_UNICODE),
            'ip_address'=>'127.0.0.1','created_at'=>date('Y-m-d H:i:s', strtotime('-' . (int)$a['days_ago'] . ' days')),
        ]);
    }

    foreach ($txData['settings'] as [$key,$value,$group]) {
        $insert('system_settings', ['setting_key'=>$key,'setting_value'=>$value,'group_name'=>$group]);
    }
};
