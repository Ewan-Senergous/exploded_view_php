<?php
if (!function_exists('basket_ve_function')) {
    function basket_ve_function($piece, $index, $variation_id) {
        $sku = htmlspecialchars($piece['Ref_fabriquant']);
        $nom_produit = htmlspecialchars($piece['Nom_produit']);
        
        ob_start();
        ?>
        <h1 style="color: red; font-size: 30px;">TEST</h1>
        <div class="accordion">
            <div class="accordion-header" onclick="toggleAccordion(<?php echo $index; ?>, event)">
                <div>
                    <div><strong>Position <?php echo $index + 1; ?></strong></div>
                    <div style="margin-top:5px;font-size:16px"><?php echo $nom_produit; ?></div>
                    <div style="font-size:14px;color:#cbd5e0">Réf: <?php echo $sku; ?></div>
                </div>
                <span class="arrow">▼</span>
            </div>
            <div id="accordion-<?php echo $index; ?>" class="accordion-content <?php echo $index === 0 ? 'active' : ''; ?>">
                <div style="background:white;border-radius:5px">
                    <?php 
                    echo implode('', array_map(fn($k, $v) => (!empty($k) && !empty($v) && $k !== "vide" && $v !== "vide") ?
                        sprintf('<div class="product-info-row" style="display:flex;margin:10px 0;"><span style="color:#2c5282;min-width:120px">%s&nbsp;:&nbsp;</span><span>%s</span></div>',
                        htmlspecialchars($k),
                        ($k === 'Ref_fabriquant' || $k === 'Nom_produit') ? '<strong>' . htmlspecialchars($v) . '</strong>' : htmlspecialchars($v)
                        ) : '', array_keys($piece), $piece));
                    ?>
                    <div class="actions-container" style="display:flex;justify-content:flex-start;align-items:start;gap:10px;margin-top:20px">
                        <div class="quantity-container" style="display:flex;align-items:center;gap:10px">
                            <label style="color:#2c5282">Quantité :</label>
                            <div style="display:flex;align-items:center">
                                <button onclick="this.nextElementSibling.stepDown()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">-</button>
                                <input type="number" value="1" min="1" style="width:50px;text-align:center;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;border-left:none;border-right:none;padding:7px 0">
                                <button onclick="this.previousElementSibling.stepUp()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">+</button>
                            </div>
                        </div>
                        <button onclick="ajouterAuPanier('<?php echo $sku; ?>',<?php echo $variation_id; ?>)" class="add-to-cart-btn" style="color:white;padding:6px 8px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;display:flex;align-items:center;gap:8px">
                            <svg width="16" height="16" viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2"><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/></svg>
                            Ajouter au panier
                        </button>
                    </div>
                    <div id="alert-<?php echo $variation_id; ?>" style="display:none;margin-top:10px;padding:15px;border-radius:5px;background-color:#4CAF50;color:white;text-align:center">
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span>✓ Produit ajouté au panier avec succès !</span>
                            <a href="<?php echo wc_get_cart_url(); ?>" style="background-color:white;color:#4CAF50;padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:bold;transition:all .3s">Voir le panier</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('basket_ve', 'basket_ve_function');