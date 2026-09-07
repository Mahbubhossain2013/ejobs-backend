<?php

namespace App\Filament\Columns;

use Illuminate\Support\Facades\Storage;
use TinusG\FilamentCompanyLogoColumn\CompanyLogoColumn as BaseColumn;
use Illuminate\Support\Js;
use Illuminate\Contracts\Support\Htmlable;

class CompanyLogoColumn extends BaseColumn
{
    public function getLogoUrl(): ?string
    {
        $record = $this->getRecord();
        
        if ($record && $record->logo) {
            $logo = $record->logo;
            if (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')) {
                return $logo;
            }
            return Storage::disk('public')->url($logo);
        }
        
        return parent::getLogoUrl();
    }

    public function toEmbeddedHtml(): string
    {
        $url = $this->getLogoUrl();
        $size = $this->getSize();
        $domain = $this->getDomain();
        $lazy = $this->isLazy();
        $dimension = $size.'px';

        $tooltip = $this->getTooltip($this->getState());
        $tooltipAttr = filled($tooltip)
            ? 'x-tooltip="{ content: '.Js::from($tooltip).', theme: $store.theme, allowHTML: '.Js::from($tooltip instanceof Htmlable).' }"'
            : '';

        ob_start(); ?>

        <div
            class="fi-ta-company-logo"
            style="display: inline-flex; align-items: center; justify-content: center; transition: transform 0.2s ease-in-out;"
            onmouseover="this.style.transform='scale(1.1)'"
            onmouseout="this.style.transform='scale(1.0)'"
            <?= $tooltipAttr ?>
        >
            <?php if ($url) { ?>
                <img
                    src="<?= e($url) ?>"
                    alt="<?= e($domain ? $domain.' logo' : 'Company logo') ?>"
                    width="<?= e($size) ?>"
                    height="<?= e($size) ?>"
                    <?php if ($lazy) { echo 'loading="lazy" decoding="async"'; } ?>
                    class="fi-ta-company-logo-image ring-1 ring-gray-950/5 dark:ring-white/10 shadow-sm"
                    style="width: <?= e($dimension) ?>; height: <?= e($dimension) ?>; border-radius: 50%; object-fit: contain; padding: 4px; background-color: #fff; display: block; border: 1px solid rgba(0,0,0,0.08);"
                />
            <?php } else { ?>
                <div
                    class="fi-ta-company-logo-placeholder ring-1 ring-gray-950/5 dark:ring-white/10 bg-gray-100 dark:bg-gray-800 shadow-inner"
                    style="width: <?= e($dimension) ?>; height: <?= e($dimension) ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; color: #6b7280; border: 1px solid rgba(0,0,0,0.05);"
                    aria-hidden="true"
                >
                    <?= e(strtoupper(substr($this->getRecord()?->name ?: ($domain ?: 'C'), 0, 2))) ?>
                </div>
            <?php } ?>
        </div>

        <?php return ob_get_clean();
    }
}
