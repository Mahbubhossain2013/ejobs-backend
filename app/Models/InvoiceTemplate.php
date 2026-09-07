<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InvoiceTemplate extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'template_view',
        'is_active',
        'is_default',
        'logo_path',
        'watermark_text',
        'watermark_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'text_color',
        'company_name',
        'company_email',
        'company_phone',
        'company_address',
        'company_website',
        'company_vat_label',
        'company_vat_number',
        'footer_text',
        'payment_instructions',
        'terms_and_conditions',
        'tax_label',
        'currency_symbol',
        'custom_css',
        'settings',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_default' => 'boolean',
        'settings'   => 'array',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the logo URL for use in views/PDFs.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    /**
     * Set this template as the default, clearing others.
     */
    public function setAsDefault(): void
    {
        self::where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Auto-get the default template (or first active).
     */
    public static function getDefault(): ?self
    {
        return self::where('is_active', true)
                   ->orderByDesc('is_default')
                   ->first();
    }

    protected static function boot(): void
    {
        parent::boot();
        static::saving(function ($template) {
            if (!$template->slug) {
                $template->slug = Str::slug($template->name);
            }
        });
    }
}
