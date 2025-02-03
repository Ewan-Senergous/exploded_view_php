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

            // Ajouter l'écouteur de clic sur les points rouges
            document.querySelectorAll('.piece-hover').forEach(point => {
                point.addEventListener('click', function(e) {
                    const position = this.getAttribute('data-position');
                    if (position) {
                        openAccordionForPosition(parseInt(position));
                    }
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