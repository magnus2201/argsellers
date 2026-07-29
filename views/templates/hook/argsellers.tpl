{*
 * 2026 ARGSEGURIDAD
 * Smarty template for rendering sellers grid v2.9.4
 * Fix destroyTimer race condition, smart popup: animate only from hidden state
 *}

<style type="text/css">
.argsellers-container {
    width: 100% !important;
    margin-bottom: 35px !important;
    box-sizing: border-box !important;
    clear: both !important;
}

.argsellers-header-title {
    text-align: center !important;
    font-size: 24px !important;
    line-height: 1.3 !important;
    margin-bottom: 25px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    display: block !important;
    border: none !important;
    padding: 0 !important;
    background: transparent !important;
}

.argseller-title-light {
    font-weight: 300 !important;
    color: #009ee3 !important;
    font-size: 24px !important;
}

.argseller-title-bold {
    font-weight: 700 !important;
    color: #009ee3 !important;
    font-size: 24px !important;
}

.argsellers-grid {
    display: flex !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
    margin-right: -4px !important;
    margin-left: -4px !important;
    position: relative !important;
    z-index: 1 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.argseller-col {
    position: relative !important;
    padding-right: 3px !important;
    padding-left: 3px !important;
    margin-bottom: 20px !important;
    box-sizing: border-box !important;
    z-index: 1 !important;
}

.argseller-col:hover {
    z-index: 9999 !important;
}

/* PC / Desktop Viewport (Force 16.666667% Column Width for 6 per row) */
@media (min-width: 992px) {
    .argsellers-grid {
        flex-wrap: nowrap !important;
        justify-content: center !important;
    }

    .argseller-col {
        flex: 0 0 16.666667% !important;
        max-width: 16.666667% !important;
        width: 16.666667% !important;
    }

    .argseller-card {
        position: relative !important;
        background: #ffffff !important;
        border-radius: 12px !important;
        padding: 14px 6px 10px 6px !important;
        text-align: center !important;
        transition: box-shadow 0.2s ease-in-out, transform 0.2s ease-in-out !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        box-sizing: border-box !important;
        cursor: default !important;
    }

    .argseller-card.argseller-active {
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-3px) !important;
        border-bottom-left-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }

    .argseller-mobile-buttons {
        display: none !important;
    }

    .argseller-desktop-only {
        display: block !important;
    }
}

/* =========================================================
   FLOATING POPUP — vive en <body>, fuera de todo stacking context
   ========================================================= */
#argsellers-floating-popup {
    display: none;
    position: absolute;
    background: #ffffff;
    border-radius: 0 0 12px 12px;
    padding: 14px 12px 18px 12px;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.20);
    z-index: 2147483647;
    box-sizing: border-box;
    text-align: center;
    min-width: 120px;
    opacity: 0;
    transform: translateY(-10px);
    transition: opacity 0.26s ease, transform 0.26s cubic-bezier(0.22, 1, 0.36, 1);
    pointer-events: none;
}

#argsellers-floating-popup.argsellers-popup-visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

/* Tablet Viewport */
@media (max-width: 991px) and (min-width: 768px) {
    .argseller-col {
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
        margin-bottom: 20px !important;
    }
    
    .argseller-card {
        background: #fff !important;
        border-radius: 10px !important;
        padding: 12px 8px !important;
        text-align: center !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
    }

    .argseller-hover-info {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        transform: none !important;
        box-shadow: none !important;
        padding: 10px 0 0 0 !important;
    }

    .argseller-qr, .argseller-desktop-only {
        display: none !important;
    }

    .argseller-mobile-buttons {
        display: block !important;
    }
}

/* Mobile Viewport */
@media (max-width: 767px) {
    .argseller-col {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding-right: 4px !important;
        padding-left: 4px !important;
        margin-bottom: 16px !important;
    }

    .argseller-card {
        background: #fff !important;
        border-radius: 10px !important;
        padding: 10px 6px !important;
        text-align: center !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05) !important;
    }

    .argseller-hover-info {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        transform: none !important;
        box-shadow: none !important;
        padding: 8px 0 0 0 !important;
    }

    .argseller-qr, .argseller-desktop-only {
        display: none !important;
    }

    .argseller-mobile-buttons {
        display: block !important;
    }
}

/* Photo Wrapper with Reset Bounds */
.argseller-photo-wrapper {
    width: 130px !important;
    height: 130px !important;
    max-width: 130px !important;
    max-height: 130px !important;
    min-width: 130px !important;
    min-height: 130px !important;
    margin: 0 auto 10px !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    border: 3px solid #f1f5f9 !important;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08) !important;
    box-sizing: border-box !important;
}

.argseller-photo-wrapper img {
    width: 100% !important;
    height: 100% !important;
    max-width: 100% !important;
    max-height: 100% !important;
    object-fit: cover !important;
    display: block !important;
    border-radius: 50% !important;
}

.argseller-name {
    font-size: 1.05rem !important;
    font-weight: 700 !important;
    color: #1e293b !important;
    margin-bottom: 3px !important;
    line-height: 1.2 !important;
}

.argseller-role {
    font-size: 0.78rem !important;
    font-weight: 600 !important;
    color: #0284c7 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.2px !important;
    margin-bottom: 6px !important;
    line-height: 1.2 !important;
}

.argseller-qr {
    width: 100px !important;
    height: 100px !important;
    margin: 0 auto 10px !important;
    border: 1px solid #e2e8f0 !important;
    padding: 4px !important;
    border-radius: 8px !important;
    background: #fff !important;
}

.argseller-qr img {
    width: 100% !important;
    height: 100% !important;
    display: block !important;
    object-fit: contain !important;
}

.argseller-phone-text {
    font-size: 0.85rem !important;
    color: #475569 !important;
    margin-bottom: 8px !important;
    font-weight: 600 !important;
}

.argseller-email-text {
    font-size: 0.78rem !important;
    color: #64748b !important;
    margin-top: 6px !important;
    word-break: break-all !important;
    font-weight: 500 !important;
}

.btn-argseller-whatsapp {
    background-color: #25d366 !important;
    color: #ffffff !important;
    border-radius: 20px !important;
    padding: 6px 10px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    margin-bottom: 6px !important;
    width: 100% !important;
    font-size: 0.78rem !important;
    border: none !important;
    transition: background-color 0.2s ease !important;
    box-sizing: border-box !important;
    cursor: pointer !important;
}

.btn-argseller-whatsapp:hover {
    background-color: #20ba54 !important;
    color: #ffffff !important;
}

.btn-argseller-whatsapp i {
    margin-right: 5px !important;
    font-size: 0.9rem !important;
}

.btn-argseller-mail {
    background-color: #ffffff !important;
    color: #ef4444 !important;
    border: 1px solid #ef4444 !important;
    border-radius: 20px !important;
    padding: 5px 10px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    width: 100% !important;
    font-size: 0.78rem !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
    cursor: pointer !important;
}

.btn-argseller-mail:hover {
    background-color: #ef4444 !important;
    color: #ffffff !important;
}

.btn-argseller-mail i {
    margin-right: 5px !important;
    font-size: 0.8rem !important;
}
</style>

{* Floating popup container — appended to <body> by JS, lives outside all stacking contexts *}
<div id="argsellers-floating-popup"></div>

<div class="argsellers-container">
    <div class="argsellers-header-title">
        <span class="argseller-title-light">ASESORES</span> <span class="argseller-title-bold">COMERCIALES</span>
    </div>

    <div class="argsellers-grid">
        {foreach from=$argsellers item=seller}
            <div class="argseller-col">
                {* data-* attributes carry all popup content — no nested hover-info div *}
                {* data-email uses nofilter so JS gets the raw value without HTML entity encoding *}
                <div class="argseller-card"
                    data-name="{$seller.name|escape:'html':'UTF-8'}"
                    data-phone="{$seller.formatted_phone|escape:'html':'UTF-8'}"
                    data-whatsapp="{$seller.clean_whatsapp|escape:'html':'UTF-8'}"
                    data-email="{$seller.full_email|escape:'javascript'}"
                    data-qr="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fapi.whatsapp.com%2Fsend%3Fphone%3D{$seller.clean_whatsapp|escape:'url'}"
                >
                    {* Profile Photo *}
                    <div class="argseller-photo-wrapper">
                        {if $seller.image && $seller.image != ''}
                            <img src="{$argsellers_img_path}{$seller.image|escape:'html':'UTF-8'}" alt="{$seller.name|escape:'html':'UTF-8'}" />
                        {else}
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%; background: #f1f5f9; display: block;">
                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#94a3b8"/>
                            </svg>
                        {/if}
                    </div>

                    {* Name and Sector *}
                    <div class="argseller-name">{$seller.name|escape:'html':'UTF-8'}</div>
                    <div class="argseller-role">{$seller.role|escape:'html':'UTF-8'}</div>

                    {* Mobile & Tablet Buttons (always visible on mobile) *}
                    <div class="argseller-mobile-buttons">
                        <a href="https://api.whatsapp.com/send?phone={$seller.clean_whatsapp|escape:'html':'UTF-8'}" class="btn-argseller-whatsapp" target="_blank" rel="noopener noreferrer">
                            <i class="fa fa-whatsapp"></i> {l s='Chatear' mod='argsellers'}
                        </a>
                        <a href="mailto:{$seller.full_email|escape:'html':'UTF-8'}" class="btn-argseller-mail">
                            <i class="fa fa-envelope"></i> {l s='Mail' mod='argsellers'}
                        </a>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>

<script type="text/javascript">
(function() {
    var popup = document.getElementById('argsellers-floating-popup');
    if (!popup) return;

    // Move popup to <body> to escape all stacking contexts
    document.body.appendChild(popup);

    var activeCard   = null;
    var hideTimer    = null;
    var destroyTimer = null;

    function buildPopupHTML(card) {
        var qr    = card.getAttribute('data-qr');
        var phone = card.getAttribute('data-phone');
        var wa    = card.getAttribute('data-whatsapp');
        var email = card.getAttribute('data-email');

        return '<div class="argseller-qr" style="width:100px;height:100px;margin:0 auto 10px;border:1px solid #e2e8f0;padding:4px;border-radius:8px;background:#fff;">'
             + '<img src="' + qr + '" style="width:100%;height:100%;display:block;object-fit:contain;" loading="lazy" />'
             + '</div>'
             + '<div class="argseller-phone-text" style="font-size:0.85rem;color:#475569;margin-bottom:8px;font-weight:600;">'
             + phone
             + '</div>'
             + '<a href="https://api.whatsapp.com/send?phone=' + wa + '" class="btn-argseller-whatsapp" target="_blank" rel="noopener noreferrer" style="display:flex;align-items:center;justify-content:center;width:100%;margin-bottom:6px;">'
             + '<i class="fa fa-whatsapp" style="margin-right:5px;"></i> Chatear'
             + '</a>'
             + '<a href="mailto:' + email + '" class="btn-argseller-mail" style="display:flex;align-items:center;justify-content:center;width:100%;">'
             + '<i class="fa fa-envelope" style="margin-right:5px;"></i> Mail'
             + '</a>'
             + '<div class="argseller-email-text" style="font-size:0.78rem;color:#64748b;margin-top:6px;word-break:break-all;font-weight:500;">'
             + email
             + '</div>';
    }

    function positionPopup(card) {
        var rect   = card.getBoundingClientRect();
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        popup.style.position = 'absolute';
        popup.style.left     = rect.left + 'px';
        popup.style.top      = (rect.bottom + scrollY) + 'px';
        popup.style.width    = rect.width + 'px';
    }

    function showPopup(card) {
        // Cancel both timers so a pending destroy never fires
        clearTimeout(hideTimer);
        clearTimeout(destroyTimer);

        var wasVisible = (popup.style.display === 'block');

        if (activeCard) activeCard.classList.remove('argseller-active');
        activeCard = card;
        card.classList.add('argseller-active');

        // Always update content and position
        popup.innerHTML = buildPopupHTML(card);
        positionPopup(card);

        if (!wasVisible) {
            // Popup was hidden: show with slide-down + fade animation
            popup.style.display = 'block';
            popup.classList.remove('argsellers-popup-visible');
            requestAnimationFrame(function() {
                requestAnimationFrame(function() {
                    popup.classList.add('argsellers-popup-visible');
                });
            });
        } else {
            // Popup already visible: content + position updated above.
            // Ensure visible class is set (might have been mid-hide)
            popup.classList.add('argsellers-popup-visible');
        }
    }

    function hidePopup() {
        hideTimer = setTimeout(function() {
            popup.classList.remove('argsellers-popup-visible');
            if (activeCard) {
                activeCard.classList.remove('argseller-active');
                activeCard = null;
            }
            // Track destroyTimer so showPopup can cancel it if needed
            destroyTimer = setTimeout(function() {
                popup.style.display = 'none';
                popup.innerHTML = '';
            }, 280);
        }, 120);
    }

    // Only activate on desktop
    function isDesktop() {
        return window.innerWidth >= 992;
    }

    var cards = document.querySelectorAll('.argseller-card');
    for (var i = 0; i < cards.length; i++) {
        (function(card) {
            card.addEventListener('mouseenter', function() {
                if (!isDesktop()) return;
                showPopup(card);
            });
            card.addEventListener('mouseleave', function() {
                if (!isDesktop()) return;
                hidePopup();
            });
        })(cards[i]);
    }

    // Keep popup visible when mouse moves into it
    popup.addEventListener('mouseenter', function() {
        clearTimeout(hideTimer);
        clearTimeout(destroyTimer);
    });
    popup.addEventListener('mouseleave', function() {
        hidePopup();
    });

    // Reposition on scroll/resize
    window.addEventListener('scroll', function() {
        if (activeCard && popup.style.display !== 'none') {
            positionPopup(activeCard);
        }
    }, { passive: true });
    window.addEventListener('resize', function() {
        if (activeCard && popup.style.display !== 'none') {
            positionPopup(activeCard);
        }
    });
})();
</script>
