<?php 
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

if(isset($_GET['order_id']) &&  $_GET['order_id'] != "" &&  $_GET['order_id'] >= 0 ){
    $woocommerce = new Client(
        'https://joinet.com/',
        'ck_1b97c8e55de58296d792f150cbeb987f0097fa34', 
        'cs_159bb5346697bd2acce4641021b86f78eace4455',
        [
            'version' => 'wc/v3', 'verify_ssl' => false
        ]
    );    
    $piezas_por_lote = 99;

    $num_order = $_GET['order_id'];
    $order = $woocommerce->get('orders/'.$num_order); 
    $order = json_decode(json_encode($order), true);
    /*echo "<pre>";
    print_r($order);
    echo "</pre>";*/
    $skus = '';
    //Articulos que conforman el pedido
    $items = $order['line_items'];
    
    if(count($items) < 100){
        foreach($items as $item){
            //se extraen los id's de cacda uno de lo articlos del pedido
            $skus.= trim($item['sku']).',';
        }
        //Se obtiene la informacion completa de cada uno de los articulos
        $articulos = $woocommerce->get('products/?per_page=100&sku='.$skus);
        $articulos = json_decode(json_encode($articulos), true);
    
        foreach($items as $item){
            $sku = $item['sku'];
            foreach($articulos as $articulo){
                if( in_array($sku, $articulo, true) ){
                    
                    if ( !empty($articulo['images'] ) ){
                        $image = $articulo['images'][0]['src'];
                        $path_img = pathinfo($image, PATHINFO_DIRNAME );
                        $name_img = pathinfo($image, PATHINFO_FILENAME );
                        $exte_img = pathinfo($image, PATHINFO_EXTENSION);
                        $thumb = $path_img."/".$name_img."-150x150.".$exte_img;
                    }else{
                        $thumb = 'https://joinet.com/wp-content/uploads/woocommerce-placeholder-150x150.png';
                    }
                    break 1;
                }
            }
            $array_products[] = array(
                "sku" => $item['sku'], 
                "producto" => $item['name'], 
                "costo" => $item['price'],
                "cantidad" => $item['quantity'],
                "total" => $item['subtotal'],
                "url_img" => $thumb
            );
        }
    }else{
        
        $lotes = count($items) / $piezas_por_lote;
        $index = 0; 
        $items_by_lote = 99;
        for($cuenta_lotes = 0; $cuenta_lotes<= $lotes; $cuenta_lotes++){

            foreach( array_slice($items, $index) as $item ){
                if($index % $items_by_lote == 0 && $index != 0  && $index != 1){
                    $index++;
                    break;    
                }
                $index++;
                $skus .= trim($item['sku']) . ',';
            }

            $articulos = $woocommerce->get('products/?per_page=100&sku='.$skus);
            $articulos = json_decode(json_encode($articulos), true);

            foreach($items as $item){
                $sku = $item['sku'];
                foreach($articulos as $articulo){
                    if( in_array($sku, $articulo, true) ){
                        
                        if ( !empty($articulo['images'] ) ){
                            $image = $articulo['images'][0]['src'];
                            $path_img = pathinfo($image, PATHINFO_DIRNAME );           
                            $name_img = pathinfo($image, PATHINFO_FILENAME );
                            $exte_img = pathinfo($image, PATHINFO_EXTENSION);
                            $thumb = $path_img."/".$name_img."-150x150.".$exte_img;
                        }else{
                            $thumb = 'https://joinet.com/wp-content/uploads/woocommerce-placeholder-150x150.png';
                        }
                        break 1;
                    }
                }
                $array_products[] = array(
                    "sku" => $item['sku'], 
                    "producto" => $item['name'], 
                    "costo" => $item['price'],
                    "cantidad" => $item['quantity'],
                    "total" => $item['subtotal'],
                    "url_img" => $thumb
                );
            }
        }
    }

    $date = new DateTime($order['date_created']);
    $fecha = $date->format('Y-m-d');
    setlocale(LC_TIME, "spanish");
    $newDate = date("d-m-Y", strtotime($fecha));				
    $date_order = strftime("%d %B, %Y", strtotime($newDate));
    $subtotal = 0;
    $piezas = 0;
    ?>
    <style>
    table, td, th {
        border: 1px solid black;
    }

    #table {
        border-collapse: collapse;
        width:792px;
    }
    #table td {
        text-align: center;
    }
    </style>
    <div class="">
        <img src="https://joinet.com/wp-content/uploads/2020/08/joinet_logo_black.png" />
    </div>
    <div class="nuevo_pedido"><b>Nuevo Pedido # <?= $num_order; ?></b></div>
    <div class="pedido_de">Has recibido el siguiente pedido de: <?= $order['billing']['first_name']." ".$order['billing']['last_name']; ?></div>
    <div class="fecha"><?= $date_order; ?></div>
    <table  id="table">
        <tr>
            <td>SKU</td>
            <td>Imagen</td>
            <td>Producto</td>
            <td>Cantidad </td>
            <td>Precio</td>
            <td>Total</td>
        </tr>
    <?php
    $subtotal = 0;
    foreach($array_products as $pro){
        $subtotal = $subtotal + $pro['total'];
        $piezas = $piezas + $pro['cantidad'];
    ?>
        <tr>
            <td><?= $pro['sku']; ?></td>
            <td ><img style="height:60px" src="<?= $pro['url_img']; ?>" /></td>
            <td><?= substr($pro['producto'], 0, 66); ?></td>
            <td><?= $pro['cantidad']; ?></td>
            <td>$<?= $pro['costo']; ?></td>
            <td>$<?= $pro['total']; ?></td>
        </tr>
    <?php
    }
    ?>
        <tr>
            <td colspan="2"></td>
            <td text-align='right'>Piezas totales:</td>
            <td><b><?= $piezas; ?></b></td>
            <td>Subtotal:</td>
            <td>$<?= $subtotal; ?></td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td></td>
            <td></td>
            <td  text-align='left'>Envió: </td>
            <td>$<?= $envio = $order['shipping_total']; ?></td>
        </tr>
        <tr>
            <td colspan="2"></td>
            <td></td>
            <td></td>
            <td text-align='left'>Total: </td>
            <td><b>$<?= $total = $subtotal + $envio; ?></b></td>
        </tr>
        
    </table>
    <div class="notas">
        <b>Nota:</b> 
        <?php         
        if ( empty( $order['customer_note']) ){
            echo ' N/A ';
         }else{
            echo $order['customer_note'];
         }
         ?>
    </div>
    <div class="envio">
        Dirección de envio:
        <ul>
            <li><b>Nombre:</b> <?= $order['billing']['first_name']. ' '. $order['billing']['last_name']; ?></li>
            <li><b>Dirección:</b> <?= $order['billing']['address_1']; ?></li>
            <li><b>Colonia:</b> <?= $order['billing']['address_2']; ?></li>
            <li><b>Ciudad:</b> <?= $order['billing']['city']; ?></li>
            <li><b>Estado:</b> <?= $order['billing']['state']; ?></li>
            <li><b>Codigo Postal:</b> <?= $order['billing']['postcode']; ?></li>
            <li><b>Teléfono:</b><?= $order['billing']['phone']; ?></li>
            <li><b>Correo:</b> <?= $order['billing']['email']; ?></li>
        </ul>
    </div>
    <?php
}else{
    header("Location: ../");
}