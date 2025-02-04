<?php
if (!function_exists('clickShowProducts_function')) {
    function clickShowProducts_function() {
        ob_start();
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fonction pour ouvrir l'accordéon correspondant à la position
            function openAccordionForPosition(position) {
                // Soustraire 1 car les index commencent à 0 mais les positions à 1
                const index = position - 1;
                const accordionContent = document.getElementById(`accordion-${index}`);
                const accordionHeader = accordionContent?.previousElementSibling;
                
                if (accordionContent && accordionHeader) {
                    // Fermer tous les accordéons d'abord
                    document.querySelectorAll('.accordion-content').forEach(content => {
                        content.style.display = 'none';
                        content.classList.remove('active');
                        content.previousElementSibling.querySelector('.arrow').innerHTML = '▼';
                    });

                    // Ouvrir l'accordéon sélectionné
                    accordionContent.style.display = 'block';
                    accordionContent.classList.add('active');
                    accordionHeader.querySelector('.arrow').innerHTML = '▲';

                    // Faire défiler jusqu'à l'accordéon
                    accordionHeader.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            // Fonction pour afficher le tooltip de la pièce correspondante
            function showPieceTooltip(index) {
                // Position = index + 1 car les positions commencent à 1
                const position = index + 1;
                const point = document.querySelector(`.piece-hover[data-position="${position}"]`);
                
                if (point) {
                    // Masquer tous les tooltips d'abord
                    document.querySelectorAll('.piece-hover .tooltip-content').forEach(tooltip => {
                        tooltip.style.display = 'none';
                    });

                    // Afficher le tooltip de la pièce sélectionnée
                    const tooltip = point.querySelector('.tooltip-content');
                    if (tooltip) {
                        tooltip.style.display = 'block';

                        // Ajout d'un effet visuel sur le point
                        point.style.transform = 'scale(1.5)';
                        point.style.backgroundColor = 'rgba(255, 0, 0, 0.8)';
                        
                        // Rétablir l'apparence après 2 secondes
                        setTimeout(() => {
                            point.style.transform = '';
                            point.style.backgroundColor = '';
                            tooltip.style.display = 'none';
                        }, 2000);
                    }
                }
            }

            // Ajouter l'écouteur de clic sur les points rouges
            document.querySelectorAll('.piece-hover').forEach(point => {
                point.addEventListener('click', function(e) {
                    const position = this.getAttribute('data-position');
                    if (position) {
                        openAccordionForPosition(parseInt(position));
                    }
                });
            });

            // Ajouter l'écouteur de clic sur les en-têtes d'accordéon
            document.querySelectorAll('.accordion-header').forEach((header, index) => {
                header.addEventListener('click', function(e) {
                    showPieceTooltip(index);
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('clickShowProducts', 'clickShowProducts_function');
?>