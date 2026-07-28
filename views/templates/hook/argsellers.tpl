{*
 * 2026 ARGSEGURIDAD
 * Smarty template for rendering sellers grid v2.0.0
 *}

<div class="argsellers-container">
    {* Header Title styled matching 'PRINCIPALES MARCAS' & 'SISTEMAS COMPLETOS' *}
    <div class="argsellers-header-title">
        <span class="argseller-title-light">ASESORES</span> <span class="argseller-title-bold">COMERCIALES</span>
    </div>

    <div class="argsellers-grid">
        {foreach from=$argsellers item=seller}
            <div class="argseller-col">
                <div class="argseller-card">
                    
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
                    
                    {* Mobile & Tablet Buttons *}
                    <div class="argseller-mobile-buttons">
                        <a href="https://api.whatsapp.com/send?phone={$seller.clean_whatsapp|escape:'html':'UTF-8'}" class="btn-argseller-whatsapp" target="_blank" rel="noopener noreferrer">
                            <i class="fa fa-whatsapp"></i> {l s='Chatear' mod='argsellers'}
                        </a>
                        <a href="mailto:{$seller.full_email|escape:'html':'UTF-8'}" class="btn-argseller-mail">
                            <i class="fa fa-envelope"></i> {l s='Mail' mod='argsellers'}
                        </a>
                    </div>

                    {* Desktop Hover Information *}
                    <div class="argseller-hover-info">
                        <div class="argseller-qr argseller-desktop-only">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https%3A%2F%2Fapi.whatsapp.com%2Fsend%3Fphone%3D{$seller.clean_whatsapp|escape:'url'}" alt="QR WhatsApp {$seller.name|escape:'html':'UTF-8'}" loading="lazy" />
                        </div>
                        
                        <div class="argseller-phone-text argseller-desktop-only">
                            {$seller.formatted_phone|escape:'html':'UTF-8'}
                        </div>

                        <a href="https://api.whatsapp.com/send?phone={$seller.clean_whatsapp|escape:'html':'UTF-8'}" class="btn-argseller-whatsapp argseller-desktop-only" target="_blank" rel="noopener noreferrer">
                            <i class="fa fa-whatsapp"></i> {l s='Chatear' mod='argsellers'}
                        </a>
                        
                        <a href="mailto:{$seller.full_email|escape:'html':'UTF-8'}" class="btn-argseller-mail argseller-desktop-only">
                            <i class="fa fa-envelope"></i> {l s='Mail' mod='argsellers'}
                        </a>
                        
                        <div class="argseller-email-text argseller-desktop-only">
                            {$seller.full_email|escape:'html':'UTF-8'}
                        </div>
                    </div>

                </div>
            </div>
        {/foreach}
    </div>
</div>
