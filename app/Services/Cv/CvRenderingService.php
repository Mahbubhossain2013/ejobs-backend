<?php

namespace App\Services\Cv;

use App\Models\CvTemplate;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CvRenderingService
{
    /**
     * Parse and render template HTML and dynamic CSS.
     */
    public function render(CvTemplate $template, array $data, array $themeSettings = []): string
    {
        try {
            $viewName = "cv_templates.{$template->slug}";
            $viewFile = str_replace('.', '/', $viewName);
            $viewPath = resource_path("views/{$viewFile}.blade.php");

            if (view()->exists($viewName) || file_exists($viewPath)) {
                $viewData = $this->buildViewData($data, $themeSettings);
                $html = view($viewName, $viewData)->render();
                $html = $this->ensureAllContentSections($html, $viewData);
                return $this->ensureSignatureBlock($html, $data);
            }

            $html = $template->html_content;
            $css = $template->css_content;

            if (empty($html)) {
                return $this->errorFallback("The template '{$template->name}' does not have any rendering code initialized.");
            }

            $this->validateSecurity($html);

            $customStyles = $this->compileThemeStyles($css, $themeSettings);

            $html = $this->injectCssAndFonts($html, $customStyles, $themeSettings);

            $data = $this->normalizeData($data);

            $viewData = $this->buildViewData($data, $themeSettings);
            $rendered = Blade::render($html, $viewData);
            $rendered = $this->ensureAllContentSections($rendered, $viewData);

            return $this->ensureSignatureBlock($rendered, $data);

        } catch (\Throwable $e) {
            Log::error("CvRenderingService Crash: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'template_slug' => $template->slug ?? 'unknown',
            ]);
            return $this->errorFallback("Template render failed: " . $e->getMessage());
        }
    }

    /**
     * Ensures that every rendered CV template contains a professional signature block at the bottom.
     */
    public function ensureSignatureBlock(string $html, array $data): string
    {
        // If template already contains a dedicated signature block, do not inject duplicate
        if (str_contains($html, 'cv-signature-section') || str_contains($html, 'signature-box')) {
            return $html;
        }

        $personal = $this->normalizePersonal($data);
        $candidateName = htmlspecialchars($personal['full_name'] ?: 'Authorized Signature', ENT_QUOTES, 'UTF-8');
        $signatureUrl = !empty($personal['signature_url']) ? htmlspecialchars($personal['signature_url'], ENT_QUOTES, 'UTF-8') : null;

        if ($signatureUrl) {
            $sigGraphic = "<div style=\"height: 40px; margin-bottom: 4px; display: flex; align-items: flex-end; justify-content: center;\"><img src=\"{$signatureUrl}\" alt=\"Signature\" style=\"max-height: 38px; max-width: 150px; object-fit: contain;\" /></div>";
        } else {
            $sigGraphic = "<div style=\"height: 35px;\"></div>";
        }

        $signatureHtml = <<<HTML
<div class="cv-signature-section" style="margin-top: 24px; padding-top: 8px; display: flex; justify-content: flex-end; page-break-inside: avoid !important; break-inside: avoid !important; width: 100%;">
    <div style="text-align: center; min-width: 190px; display: inline-block;">
        {$sigGraphic}
        <div style="border-top: 1.5px solid #334155; width: 180px; margin: 0 auto 5px auto;"></div>
        <div style="font-size: 13px; font-weight: 700; color: #1e293b; letter-spacing: 0.3px;">{$candidateName}</div>
        <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;">স্বাক্ষর ও তারিখ / Signature & Date</div>
    </div>
</div>
HTML;

        // Match the closing sequence at the very end of the file right before </body>
        // This guarantees the signature is placed inside the content column at the very end of the document
        // on the final page (page 1 if 1 page, page 2 if 2 pages, page 3 if 3 pages)
        if (preg_match('/((?:\s*<\/div>)+\s*<\/body>)/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            $closingSeq = $matches[0][0];
            $offset = $matches[0][1];
            $divCount = preg_match_all('/<\/div>/i', $closingSeq);
            if ($divCount >= 2) {
                $replacedSeq = preg_replace('/(<\/div>)/i', "{$signatureHtml}\n$1", $closingSeq, 1);
                return substr_replace($html, $replacedSeq, $offset, strlen($closingSeq));
            } else {
                $replacedSeq = "{$signatureHtml}\n" . $closingSeq;
                return substr_replace($html, $replacedSeq, $offset, strlen($closingSeq));
            }
        }

        if (preg_match('/(<\/body>)/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            return substr_replace($html, "{$signatureHtml}\n</body>", $matches[0][1], strlen($matches[0][0]));
        }

        return $html . "\n" . $signatureHtml;
    }

    /**
     * Inspects rendered template HTML and ensures that ANY section or personal field
     * filled by the user that was omitted by the specific template design is
     * seamlessly injected into the document flow before the signature.
     */
    public function ensureAllContentSections(string $html, array $viewData): string
    {
        $appendedHtml = '';

        // 1. Check Missing Personal Biographical Information
        $candidate = $viewData['candidate'] ?? [];
        $personalItems = [];
        if (!empty($candidate['father_name']) && !$this->htmlContainsValue($html, $candidate['father_name'])) {
            $personalItems[] = ['label' => "Father's Name / পিতার নাম", 'value' => $candidate['father_name']];
        }
        if (!empty($candidate['mother_name']) && !$this->htmlContainsValue($html, $candidate['mother_name'])) {
            $personalItems[] = ['label' => "Mother's Name / মাতার নাম", 'value' => $candidate['mother_name']];
        }
        if (!empty($candidate['dob']) && !$this->htmlContainsValue($html, $candidate['dob'])) {
            $personalItems[] = ['label' => 'Date of Birth / জন্মতারিখ', 'value' => $candidate['dob']];
        }
        if (!empty($candidate['place_of_birth']) && !$this->htmlContainsValue($html, $candidate['place_of_birth'])) {
            $personalItems[] = ['label' => 'Place of Birth / জন্মস্থান', 'value' => $candidate['place_of_birth']];
        }
        if (!empty($candidate['gender']) && !$this->htmlContainsValue($html, $candidate['gender'])) {
            $personalItems[] = ['label' => 'Gender / লিঙ্গ', 'value' => ucfirst($candidate['gender'])];
        }
        if (!empty($candidate['marital_status']) && !$this->htmlContainsValue($html, $candidate['marital_status'])) {
            $personalItems[] = ['label' => 'Marital Status / বৈবাহিক অবস্থা', 'value' => ucfirst($candidate['marital_status'])];
        }
        if (!empty($candidate['nationality']) && !$this->htmlContainsValue($html, $candidate['nationality'])) {
            $personalItems[] = ['label' => 'Nationality / জাতীয়তা', 'value' => $candidate['nationality']];
        }
        if (!empty($candidate['religion']) && !$this->htmlContainsValue($html, $candidate['religion'])) {
            $personalItems[] = ['label' => 'Religion / ধর্ম', 'value' => $candidate['religion']];
        }
        if (!empty($candidate['blood_group']) && !$this->htmlContainsValue($html, $candidate['blood_group'])) {
            $personalItems[] = ['label' => 'Blood Group / রক্তের গ্রুপ', 'value' => $candidate['blood_group']];
        }
        if (!empty($candidate['nid']) && !$this->htmlContainsValue($html, $candidate['nid'])) {
            $personalItems[] = ['label' => 'National ID / জাতীয় পরিচয়পত্র', 'value' => $candidate['nid']];
        }
        if (!empty($candidate['driving_license']) && !$this->htmlContainsValue($html, $candidate['driving_license'])) {
            $personalItems[] = ['label' => 'Driving License / ড্রাইভিং লাইসেন্স', 'value' => $candidate['driving_license']];
        }
        if (!empty($candidate['alt_phone']) && !$this->htmlContainsValue($html, $candidate['alt_phone'])) {
            $personalItems[] = ['label' => 'Alt Contact / বিকল্প যোগাযোগ', 'value' => $candidate['alt_phone']];
        }
        if (!empty($candidate['permanent_address']) && !$this->htmlContainsValue($html, $candidate['permanent_address'])) {
            $personalItems[] = ['label' => 'Permanent Address / স্থায়ী ঠিকানা', 'value' => $candidate['permanent_address']];
        }

        if (!empty($personalItems)) {
            $gridContent = '';
            foreach ($personalItems as $item) {
                $lbl = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
                $val = htmlspecialchars($item['value'], ENT_QUOTES, 'UTF-8');
                $gridContent .= "<div style=\"display: flex; gap: 6px; align-items: baseline;\"><strong style=\"color: #334155; min-width: 110px; font-weight: 600;\">{$lbl}:</strong><span style=\"color: #475569;\">{$val}</span></div>";
            }
            $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-personal-details" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Personal Information / ব্যক্তিগত তথ্যাবলী</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 5px 16px; font-size: 9.5px; line-height: 1.5;">
        {$gridContent}
    </div>
</div>
HTML;
        }

        // 2. Check Missing Languages
        $languages = $viewData['languages'] ?? [];
        if (!empty($languages) && !$this->htmlContainsItemName($html, $languages, ['name', 'language'])) {
            $langPills = '';
            foreach ($languages as $lang) {
                $name = htmlspecialchars(is_array($lang) ? ($lang['name'] ?? $lang['language'] ?? '') : (string)$lang, ENT_QUOTES, 'UTF-8');
                $prof = htmlspecialchars(is_array($lang) ? ($lang['proficiency'] ?? $lang['level'] ?? '') : '', ENT_QUOTES, 'UTF-8');
                $badge = $prof ? " <span style=\"opacity: 0.75; font-size: 8.5px;\">({$prof})</span>" : "";
                if ($name) {
                    $langPills .= "<span style=\"display: inline-block; font-size: 9.5px; padding: 2px 8px; background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.1); border-radius: 4px; margin-right: 4px; margin-bottom: 4px;\">{$name}{$badge}</span>";
                }
            }
            if ($langPills) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-languages-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Languages / ভাষা দক্ষতা</div>
    <div style="display: flex; flex-wrap: wrap; gap: 4px;">{$langPills}</div>
</div>
HTML;
            }
        }

        // 3. Check Missing Certifications
        $certifications = $viewData['certifications'] ?? [];
        if (!empty($certifications) && !$this->htmlContainsItemName($html, $certifications, ['name', 'title'])) {
            $certCards = '';
            foreach ($certifications as $cert) {
                $name = htmlspecialchars($cert['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $issuer = htmlspecialchars($cert['issuer'] ?? '', ENT_QUOTES, 'UTF-8');
                $date = htmlspecialchars($cert['date'] ?? '', ENT_QUOTES, 'UTF-8');
                if ($name) {
                    $sub = $issuer . ($date ? " ({$date})" : "");
                    $certCards .= "<div style=\"margin-bottom: 6px; font-size: 10px;\"><strong style=\"color: #1e293b;\">{$name}</strong>" . ($sub ? "<div style=\"font-size: 9px; opacity: 0.8;\">{$sub}</div>" : "") . "</div>";
                }
            }
            if ($certCards) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-certifications-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Certifications / সনদপত্র</div>
    <div>{$certCards}</div>
</div>
HTML;
            }
        }

        // 4. Check Missing Training & Courses
        $training = $viewData['training'] ?? [];
        if (!empty($training) && !$this->htmlContainsItemName($html, $training, ['title', 'name'])) {
            $trainingCards = '';
            foreach ($training as $t) {
                $title = htmlspecialchars($t['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $inst = htmlspecialchars($t['institute'] ?? $t['institution'] ?? '', ENT_QUOTES, 'UTF-8');
                $dur = htmlspecialchars($t['duration'] ?? '', ENT_QUOTES, 'UTF-8');
                if ($title) {
                    $meta = $inst . ($dur ? " · Duration: {$dur}" : "");
                    $trainingCards .= "<div style=\"margin-bottom: 6px; font-size: 10px;\"><strong style=\"color: #1e293b;\">{$title}</strong>" . ($meta ? "<div style=\"font-size: 9px; opacity: 0.8;\">{$meta}</div>" : "") . "</div>";
                }
            }
            if ($trainingCards) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-training-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Training & Professional Courses / প্রশিক্ষণ</div>
    <div>{$trainingCards}</div>
</div>
HTML;
            }
        }

        // 5. Check Missing Projects
        $projects = $viewData['projects'] ?? [];
        if (!empty($projects) && !$this->htmlContainsItemName($html, $projects, ['name', 'title'])) {
            $projCards = '';
            foreach ($projects as $p) {
                $name = htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $url = htmlspecialchars($p['url'] ?? '', ENT_QUOTES, 'UTF-8');
                $desc = nl2br(htmlspecialchars($p['description'] ?? '', ENT_QUOTES, 'UTF-8'));
                if ($name) {
                    $link = $url ? " <a href=\"{$url}\" target=\"_blank\" style=\"font-size: 9px; text-decoration: underline; opacity: 0.85;\">{$url}</a>" : "";
                    $projCards .= "<div style=\"margin-bottom: 8px; font-size: 10px;\"><div style=\"display: flex; justify-content: space-between; align-items: baseline;\"><strong style=\"color: #1e293b;\">{$name}</strong>{$link}</div>" . ($desc ? "<div style=\"font-size: 9.5px; line-height: 1.45; opacity: 0.85; margin-top: 2px;\">{$desc}</div>" : "") . "</div>";
                }
            }
            if ($projCards) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-projects-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Key Projects / প্রকল্পসমূহ</div>
    <div>{$projCards}</div>
</div>
HTML;
            }
        }

        // 6. Check Missing Awards & Achievements
        $awards = $viewData['awards'] ?? [];
        if (!empty($awards) && !$this->htmlContainsItemName($html, $awards, ['name', 'title', 'description'])) {
            $awardList = '';
            foreach ($awards as $a) {
                $name = htmlspecialchars(is_array($a) ? ($a['name'] ?? $a['description'] ?? '') : (string)$a, ENT_QUOTES, 'UTF-8');
                if ($name) {
                    $awardList .= "<li style=\"margin-bottom: 3px;\">{$name}</li>";
                }
            }
            if ($awardList) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-awards-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Honors & Awards / পুরস্কার ও স্বীকৃতি</div>
    <ul style="padding-left: 18px; margin: 0; font-size: 9.5px; line-height: 1.5;">{$awardList}</ul>
</div>
HTML;
            }
        }

        // 7. Check Missing References
        $references = $viewData['references'] ?? [];
        if (!empty($references) && !$this->htmlContainsItemName($html, $references, ['name'])) {
            $refCards = '';
            foreach ($references as $r) {
                $name = htmlspecialchars($r['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $desig = htmlspecialchars($r['designation'] ?? '', ENT_QUOTES, 'UTF-8');
                $org = htmlspecialchars($r['organization'] ?? '', ENT_QUOTES, 'UTF-8');
                $phone = htmlspecialchars($r['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                $email = htmlspecialchars($r['email'] ?? '', ENT_QUOTES, 'UTF-8');
                $rel = htmlspecialchars($r['relation'] ?? '', ENT_QUOTES, 'UTF-8');
                if ($name) {
                    $contactLine = ($phone ? "📞 {$phone} " : "") . ($email ? "✉️ {$email}" : "");
                    $refCards .= "<div style=\"padding: 6px 10px; background: rgba(0,0,0,0.02); border-left: 2.5px solid currentColor; font-size: 9.5px; line-height: 1.4;\"><strong style=\"color: #1e293b; font-size: 10.5px;\">{$name}</strong>" . ($desig ? "<div>{$desig}" . ($org ? ", {$org}" : "") . "</div>" : "") . ($rel ? "<div style=\"font-size: 8.5px; opacity: 0.7;\">Relation: {$rel}</div>" : "") . ($contactLine ? "<div style=\"font-size: 9px; opacity: 0.85; margin-top: 2px;\">{$contactLine}</div>" : "") . "</div>";
                }
            }
            if ($refCards) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-references-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">References / রেফারেন্স</div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 8px;">{$refCards}</div>
</div>
HTML;
            }
        }

        // 8. Check Missing Hobbies & Interests
        $hobbies = $viewData['hobbies'] ?? [];
        if (!empty($hobbies) && !$this->htmlContainsItemName($html, $hobbies, ['name'])) {
            $hobbyTags = '';
            foreach ($hobbies as $h) {
                $name = htmlspecialchars(is_array($h) ? ($h['name'] ?? reset($h)) : (string)$h, ENT_QUOTES, 'UTF-8');
                if ($name) {
                    $hobbyTags .= "<span style=\"display: inline-block; font-size: 9.5px; padding: 2px 8px; background: rgba(0,0,0,0.04); border-radius: 4px; margin-right: 4px; margin-bottom: 4px;\">{$name}</span>";
                }
            }
            if ($hobbyTags) {
                $appendedHtml .= <<<HTML
<div class="cv-appended-section cv-hobbies-section" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">Hobbies & Interests / শখ ও আগ্রহ</div>
    <div style="display: flex; flex-wrap: wrap; gap: 4px;">{$hobbyTags}</div>
</div>
HTML;
            }
        }

        // 9. Check Missing Custom Sections
        $customSections = $viewData['custom_sections'] ?? [];
        if (!empty($customSections)) {
            $customBlocks = '';
            foreach ($customSections as $cs) {
                $title = htmlspecialchars($cs['title'] ?? 'Additional Section', ENT_QUOTES, 'UTF-8');
                $desc = nl2br(htmlspecialchars($cs['description'] ?? '', ENT_QUOTES, 'UTF-8'));
                if (!$this->htmlContainsValue($html, $title)) {
                    $customBlocks .= <<<HTML
<div class="cv-appended-section cv-custom-block" style="margin-top: 14px; page-break-inside: avoid !important; break-inside: avoid !important;">
    <div class="sec-title section-label main-section-title" style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid currentColor; opacity: 0.85; padding-bottom: 3px; margin-bottom: 8px;">{$title}</div>
    <div style="font-size: 10px; line-height: 1.55; opacity: 0.9;">{$desc}</div>
</div>
HTML;
                }
            }
            if ($customBlocks) {
                $appendedHtml .= $customBlocks;
            }
        }

        // If nothing was missing, return raw HTML unmodified
        if (empty(trim($appendedHtml))) {
            return $html;
        }

        // Injection: Find where to place the appended sections:
        // Try inside main content container right before its closing </div>
        $injected = false;
        $mainPanelRegexes = [
            '/(<div[^>]*class="[^"]*(?:right-panel|right-col|main-content|col-right|main)[^"]*"[^>]*>[\s\S]*?)(<\/div>\s*<\/div>\s*<\/body>)/i',
            '/(<div[^>]*class="[^"]*(?:right-panel|right-col|main-content|col-right|main)[^"]*"[^>]*>[\s\S]*?)(<\/div>\s*<\/div>)/i',
        ];
        foreach ($mainPanelRegexes as $regex) {
            if (preg_match($regex, $html, $matches, PREG_OFFSET_CAPTURE)) {
                $pos = $matches[1][1] + strlen($matches[1][0]);
                $html = substr_replace($html, "{$appendedHtml}\n", $pos, 0);
                $injected = true;
                break;
            }
        }

        // Otherwise inject inside .cv-page before closing </div>
        if (!$injected) {
            if (preg_match('/((?:\s*<\/div>)+\s*<\/body>)/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
                $closingSeq = $matches[0][0];
                $offset = $matches[0][1];
                $divCount = preg_match_all('/<\/div>/i', $closingSeq);
                if ($divCount >= 2) {
                    $replacedSeq = preg_replace('/(<\/div>)/i', "{$appendedHtml}\n$1", $closingSeq, 1);
                    $html = substr_replace($html, $replacedSeq, $offset, strlen($closingSeq));
                } else {
                    $replacedSeq = "{$appendedHtml}\n" . $closingSeq;
                    $html = substr_replace($html, $replacedSeq, $offset, strlen($closingSeq));
                }
                $injected = true;
            }
        }

        if (!$injected && str_contains($html, '</body>')) {
            $html = str_replace('</body>', "{$appendedHtml}\n</body>", $html);
        }

        return $html;
    }

    private function htmlContainsValue(string $html, string $val): bool
    {
        $clean = trim($val);
        if (mb_strlen($clean) < 2) return true;
        return str_contains($html, $clean) || str_contains($html, htmlspecialchars($clean, ENT_QUOTES, 'UTF-8'));
    }

    private function htmlContainsItemName(string $html, array $items, array $keyCandidates): bool
    {
        if (empty($items)) return true;
        foreach ($items as $item) {
            if (is_array($item)) {
                foreach ($keyCandidates as $key) {
                    if (!empty($item[$key])) {
                        $val = trim((string)$item[$key]);
                        if (mb_strlen($val) >= 3 && (str_contains($html, $val) || str_contains($html, htmlspecialchars($val, ENT_QUOTES, 'UTF-8')))) {
                            return true;
                        }
                    }
                }
            } elseif (is_string($item)) {
                $val = trim($item);
                if (mb_strlen($val) >= 3 && (str_contains($html, $val) || str_contains($html, htmlspecialchars($val, ENT_QUOTES, 'UTF-8')))) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Build the view data array passed to Blade templates.
     */
    private function buildViewData(array $data, array $themeSettings): array
    {
        $personal = $this->normalizePersonal($data);

        $summary = $data['summary'] 
            ?? $data['resume_objective']['description'] 
            ?? (is_string($data['resume_objective'] ?? null) ? $data['resume_objective'] : null)
            ?? $data['objective']
            ?? $data['career_objective']
            ?? $data['bio']
            ?? $data['about']
            ?? $personal['summary']
            ?? $personal['bio']
            ?? '';

        $photoUrl = $personal['photo_url'] ?? $data['photo_url'] ?? null;
        if ($photoUrl && !str_starts_with($photoUrl, 'data:')) {
            if (str_starts_with($photoUrl, 'http://') && (request()->isSecure() || str_contains($photoUrl, 'ejobs.bd'))) {
                $photoUrl = preg_replace('/^http:/', 'https:', $photoUrl);
            } elseif (!str_starts_with($photoUrl, 'http')) {
                $cleanPath = ltrim($photoUrl, '/');
                if (str_starts_with($cleanPath, 'storage/')) {
                    $cleanPath = substr($cleanPath, 8);
                }
                $photoUrl = asset('storage/' . $cleanPath);
                if (request()->isSecure() || str_contains($photoUrl, 'ejobs.bd')) {
                    $photoUrl = preg_replace('/^http:/', 'https:', $photoUrl);
                }
            }
        }
        if ($photoUrl) {
            $personal['photo_url'] = $photoUrl;
        }

        $socialLinks = $data['social_links'] ?? $personal['social_links'] ?? [];
        if (!empty($socialLinks) && is_array($socialLinks)) {
            $firstValue = reset($socialLinks);
            if (is_string($firstValue) || is_null($firstValue)) {
                $normalized = [];
                foreach ($socialLinks as $platform => $url) {
                    if (!empty($url)) {
                        $normalized[] = [
                            'platform' => $platform,
                            'url' => $url,
                            'label' => ucfirst($platform),
                        ];
                    }
                }
                $socialLinks = $normalized;
            }
        }

        return [
            'data' => $data,
            'theme' => $themeSettings,
            'candidate' => $personal,
            'photo_url' => $photoUrl,
            'summary' => $summary,
            'skills' => $this->normalizeSkills($data['skills'] ?? []),
            'experience' => $this->normalizeExperience($data['experience'] ?? $data['experiences'] ?? $data['work_experience'] ?? []),
            'education' => $this->normalizeEducation($data['education'] ?? $data['educations'] ?? []),
            'projects' => $this->normalizeProjects($data['projects'] ?? []),
            'certifications' => $this->normalizeCertifications($data['certifications'] ?? []),
            'awards' => $this->normalizeAwards($data['awards'] ?? $data['achievements'] ?? []),
            'languages' => $this->normalizeLanguages($data['languages'] ?? []),
            'references' => $this->normalizeReferences($data['references'] ?? []),
            'training' => $this->normalizeTraining($data['training'] ?? $data['trainings'] ?? []),
            'social_links' => $socialLinks,
            'hobbies' => $this->normalizeHobbies($data['hobbies'] ?? $data['interests'] ?? []),
            'custom_sections' => $this->normalizeCustomSections($data['custom_sections'] ?? []),
        ];
    }

    private function normalizePersonal(array $data): array
    {
        $raw = $data['personal'] ?? $data['personal_info'] ?? $data;

        $fullName = $raw['full_name'] 
            ?? trim(($raw['first_name'] ?? '') . ' ' . ($raw['last_name'] ?? ''))
            ?: ($data['name'] ?? '');

        $title = $raw['title'] 
            ?? $raw['current_position'] 
            ?? $raw['job_title'] 
            ?? $raw['designation'] 
            ?? '';

        if (!empty($title) && !empty($fullName) && $title === $fullName) {
            $title = '';
        }

        $location = $raw['location'] ?? $raw['city'] ?? $raw['address'] ?? '';

        return [
            'full_name' => $fullName ?: 'Your Name',
            'first_name' => $raw['first_name'] ?? '',
            'last_name' => $raw['last_name'] ?? '',
            'title' => $title,
            'current_position' => $title,
            'email' => $raw['email'] ?? '',
            'phone' => $raw['phone'] ?? $raw['contact_number'] ?? $raw['mobile'] ?? '',
            'location' => $location,
            'address' => $raw['address'] ?? '',
            'city' => $raw['city'] ?? '',
            'zip_code' => $raw['zip_code'] ?? '',
            'website' => $raw['website'] ?? $raw['portfolio_url'] ?? '',
            'linkedin' => $raw['linkedin'] ?? $raw['linkedin_url'] ?? '',
            'github' => $raw['github'] ?? $raw['github_url'] ?? '',
            'dob' => $raw['dob'] ?? $raw['date_of_birth'] ?? '',
            'date_of_birth' => $raw['date_of_birth'] ?? $raw['dob'] ?? '',
            'place_of_birth' => $raw['place_of_birth'] ?? '',
            'gender' => $raw['gender'] ?? '',
            'nationality' => $raw['nationality'] ?? '',
            'marital_status' => $raw['marital_status'] ?? '',
            'driving_license' => $raw['driving_license'] ?? '',
            'father_name' => $raw['father_name'] ?? '',
            'mother_name' => $raw['mother_name'] ?? '',
            'religion' => $raw['religion'] ?? '',
            'blood_group' => $raw['blood_group'] ?? '',
            'nid' => $raw['nid'] ?? $raw['national_id'] ?? '',
            'present_address' => $raw['present_address'] ?? $raw['address'] ?? '',
            'permanent_address' => $raw['permanent_address'] ?? '',
            'alt_phone' => $raw['alt_phone'] ?? $raw['alternate_phone'] ?? '',
            'photo_url' => $raw['photo_url'] ?? $raw['avatar'] ?? null,
            'signature_url' => $raw['signature_url'] ?? $data['signature_url'] ?? null,
            'summary' => $raw['summary'] ?? $raw['bio'] ?? '',
        ];
    }

        private function normalizeExperience(array $experiences): array
    {
        $filtered = [];
        foreach ($experiences as $e) {
            if (!is_array($e) && !is_object($e)) continue;
            $arr = (array)$e;
            $company = trim($arr['company'] ?? $arr['employer'] ?? $arr['company_name'] ?? $arr['organization'] ?? '');
            $position = trim($arr['position'] ?? $arr['job_title'] ?? $arr['title'] ?? $arr['designation'] ?? '');
            $description = trim($arr['description'] ?? $arr['responsibilities'] ?? $arr['details'] ?? '');
            if (!empty($company) || !empty($position) || !empty($description)) {
                $filtered[] = [
                    'position' => $position,
                    'title' => $position,
                    'company' => $company,
                    'company_name' => $company,
                    'start_date' => trim($arr['start_date'] ?? $arr['from'] ?? ''),
                    'end_date' => trim($arr['end_date'] ?? $arr['to'] ?? '') ?: null,
                    'location' => trim($arr['location'] ?? $arr['city'] ?? ''),
                    'employment_type' => trim($arr['employment_type'] ?? ''),
                    'description' => $description,
                    'is_current' => !empty($arr['is_current']),
                ];
            }
        }
        return $filtered;
    }

    private function normalizeEducation(array $educations): array
    {
        $filtered = [];
        foreach ($educations as $ed) {
            if (!is_array($ed) && !is_object($ed)) continue;
            $arr = (array)$ed;
            $institution = trim($arr['institution'] ?? $arr['school'] ?? $arr['institute_name'] ?? $arr['university'] ?? $arr['college'] ?? '');
            $degree = trim($arr['degree'] ?? $arr['degree_name'] ?? $arr['qualification'] ?? $arr['level'] ?? '');
            $description = trim($arr['description'] ?? $arr['field_of_study'] ?? $arr['group_or_subject'] ?? '');
            if (!empty($institution) || !empty($degree) || !empty($description)) {
                $filtered[] = [
                    'degree' => $degree,
                    'qualification' => $degree,
                    'institution' => $institution,
                    'school' => $institution,
                    'start_date' => trim($arr['start_date'] ?? $arr['from'] ?? $arr['year'] ?? ''),
                    'end_date' => trim($arr['end_date'] ?? $arr['to'] ?? ''),
                    'location' => trim($arr['location'] ?? $arr['city'] ?? ''),
                    'grade' => trim($arr['grade'] ?? $arr['result'] ?? $arr['cgpa'] ?? $arr['gpa'] ?? ''),
                    'board' => trim($arr['board'] ?? ''),
                    'field_of_study' => trim($arr['field_of_study'] ?? ''),
                    'description' => $description,
                ];
            }
        }
        return $filtered;
    }

    private function normalizeSkills(array $skills): array
    {
        $filtered = [];
        foreach ($skills as $s) {
            $name = '';
            $level = null;
            $category = null;
            if (is_array($s)) {
                $name = trim($s['name'] ?? $s['skill'] ?? $s['label'] ?? $s['title'] ?? reset($s) ?? '');
                $level = $s['level'] ?? $s['proficiency'] ?? null;
                $category = $s['category'] ?? $s['type'] ?? null;
            } elseif (is_object($s)) {
                $name = trim($s->name ?? $s->skill ?? $s->label ?? $s->title ?? '');
                $level = $s->level ?? $s->proficiency ?? null;
                $category = $s->category ?? $s->type ?? null;
            } elseif (is_string($s)) {
                $name = trim($s);
            }
            if (!empty($name)) {
                $filtered[] = [
                    'name' => $name,
                    'level' => $level,
                    'category' => $category,
                ];
            }
        }
        return $filtered;
    }

    private function normalizeLanguages(array $languages): array
    {
        $filtered = [];
        foreach ($languages as $l) {
            $name = '';
            $prof = null;
            if (is_array($l)) {
                $name = trim($l['name'] ?? $l['language'] ?? reset($l) ?? '');
                $prof = $l['proficiency'] ?? $l['level'] ?? null;
            } elseif (is_object($l)) {
                $name = trim($l->name ?? $l->language ?? '');
                $prof = $l->proficiency ?? $l->level ?? null;
            } elseif (is_string($l)) {
                $name = trim($l);
            }
            if (!empty($name)) {
                $filtered[] = [
                    'name' => $name,
                    'proficiency' => $prof,
                ];
            }
        }
        return $filtered;
    }

    private function normalizeHobbies(array $hobbies): array
    {
        $filtered = [];
        foreach ($hobbies as $h) {
            $name = '';
            if (is_array($h)) {
                $name = trim($h['name'] ?? $h['hobby'] ?? $h['title'] ?? $h['interest'] ?? reset($h) ?? '');
            } elseif (is_object($h)) {
                $name = trim($h->name ?? $h->hobby ?? $h->title ?? $h->interest ?? '');
            } elseif (is_string($h)) {
                $name = trim($h);
            }
            if (!empty($name)) {
                $filtered[] = ['name' => $name];
            }
        }
        return $filtered;
    }

    private function normalizeReferences(array $references): array
    {
        $filtered = [];
        foreach ($references as $r) {
            if (!is_array($r) && !is_object($r)) continue;
            $arr = (array)$r;
            $name = trim($arr['name'] ?? $arr['full_name'] ?? '');
            $designation = trim($arr['designation'] ?? $arr['title'] ?? $arr['position'] ?? '');
            $organization = trim($arr['organization'] ?? $arr['company'] ?? '');
            $phone = trim($arr['phone'] ?? $arr['mobile'] ?? '');
            $email = trim($arr['email'] ?? '');
            if (!empty($name) || !empty($designation) || !empty($organization) || !empty($phone) || !empty($email)) {
                $filtered[] = [
                    'name' => $name,
                    'designation' => $designation,
                    'organization' => $organization,
                    'phone' => $phone,
                    'email' => $email,
                    'relation' => trim($arr['relation'] ?? ''),
                ];
            }
        }
        return $filtered;
    }

    private function normalizeCustomSections(array $sections): array
    {
        $filtered = [];
        foreach ($sections as $s) {
            if (!is_array($s) && !is_object($s)) continue;
            $arr = (array)$s;
            $title = trim($arr['title'] ?? $arr['name'] ?? '');
            $desc = trim($arr['description'] ?? $arr['content'] ?? $arr['details'] ?? '');
            if (!empty($title) || !empty($desc)) {
                $filtered[] = [
                    'title' => $title ?: 'Additional Section',
                    'description' => $desc,
                ];
            }
        }
        return $filtered;
    }

    private function normalizeProjects(array $projects): array
    {
        $filtered = [];
        foreach ($projects as $p) {
            if (!is_array($p) && !is_object($p)) continue;
            $arr = (array)$p;
            $name = trim($arr['name'] ?? $arr['title'] ?? '');
            $desc = trim($arr['description'] ?? '');
            if (!empty($name) || !empty($desc)) {
                $filtered[] = [
                    'name' => $name,
                    'title' => $name,
                    'description' => $desc,
                    'url' => trim($arr['url'] ?? $arr['link'] ?? ''),
                    'role' => trim($arr['role'] ?? ''),
                    'technologies' => (array)($arr['technologies'] ?? []),
                ];
            }
        }
        return $filtered;
    }

    private function normalizeCertifications(array $certifications): array
    {
        $filtered = [];
        foreach ($certifications as $c) {
            if (!is_array($c) && !is_object($c)) continue;
            $arr = (array)$c;
            $name = trim($arr['name'] ?? $arr['title'] ?? $arr['certificate_name'] ?? '');
            if (!empty($name)) {
                $filtered[] = [
                    'name' => $name,
                    'issuer' => trim($arr['issuer'] ?? $arr['organization'] ?? $arr['institute'] ?? ''),
                    'date' => trim($arr['date'] ?? $arr['year'] ?? $arr['issue_date'] ?? ''),
                ];
            }
        }
        return $filtered;
    }

    private function normalizeAwards(array $awards): array
    {
        $filtered = [];
        foreach ($awards as $a) {
            $name = '';
            if (is_array($a)) {
                $name = trim($a['name'] ?? $a['title'] ?? $a['award_name'] ?? reset($a) ?? '');
            } elseif (is_object($a)) {
                $name = trim($a->name ?? $a->title ?? $a->award_name ?? '');
            } elseif (is_string($a)) {
                $name = trim($a);
            }
            if (!empty($name)) {
                $filtered[] = ['name' => $name];
            }
        }
        return $filtered;
    }

    private function normalizeTraining(array $training): array
    {
        $filtered = [];
        foreach ($training as $t) {
            if (!is_array($t) && !is_object($t)) continue;
            $arr = (array)$t;
            $title = trim($arr['title'] ?? $arr['name'] ?? $arr['training_name'] ?? '');
            if (!empty($title)) {
                $filtered[] = [
                    'title' => $title,
                    'institution' => trim($arr['institution'] ?? $arr['institute'] ?? ''),
                    'institute' => trim($arr['institution'] ?? $arr['institute'] ?? ''),
                    'duration' => trim($arr['duration'] ?? ''),
                ];
            }
        }
        return $filtered;
    }

    /**
     * Run high-security filters to prevent PHP injection or XSS execution.
     */
    public function validateSecurity(string $code): void
    {
        if (preg_match('/<\?php/i', $code) || preg_match('/<\?/i', $code)) {
            throw new \RuntimeException("Security breach: Dynamic templates are not allowed to declare manual PHP code blocks.");
        }

        if (preg_match('/<script\b(?![^>]*\bsrc\s*=)[^>]*>(.*?)<\/script>/is', $code, $scriptMatches)) {
            $scriptContent = $scriptMatches[1];
            $dangerousPatterns = [
                '/\beval\s*\(/i',
                '/\bdocument\s*\.\s*cookie/i',
                '/\bdocument\s*\.\s*write\s*\(/i',
                '/\bwindow\s*\.\s*location/i',
                '/\blocalStorage\b/i',
                '/\bsessionStorage\b/i',
                '/\bfetch\s*\(\s*["\']https?:\/\//i',
                '/\bXMLHttpRequest\b/i',
                '/\bnew\s+Function\s*\(/i',
                '/<\?php/i',
                '/<\?=/i',
            ];
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $scriptContent)) {
                    throw new \RuntimeException("Security breach: Template contains dangerous script content.");
                }
            }
        }

        $unsafeKeywords = [
            'exec(', 'system', 'shell_exec', 'passthru', 'popen', 'proc_open', 'eval', 'assert',
            'file_get_contents', 'file_put_contents', 'unlink', 'rmdir', 'mkdir',
            'DB::', 'Schema::', 'env(', 'config(', 'request(', 'session(', 'Auth::', 'Cookie::'
        ];

        foreach ($unsafeKeywords as $keyword) {
            if (str_contains($code, $keyword)) {
                throw new \RuntimeException("Security violation: Template contains unsafe function or class access: '{$keyword}'");
            }
        }
    }

    /**
     * Previously stripped skills/languages to flat strings, destroying
     * level/proficiency data before buildViewData could normalize them.
     * Now a no-op — normalization happens in buildViewData() via
     * normalizeSkills() and normalizeLanguages() which preserve all fields.
     */
    private function normalizeData(array $data): array
    {
        return $data;
    }

    /**
     * Map configuration styling tokens to custom CSS variables.
     */
    private function compileThemeStyles(?string $css, array $themeSettings): string
    {
        $primaryColor = $themeSettings['colors']['primary'] ?? '#2563eb';
        $secondaryColor = $themeSettings['colors']['secondary'] ?? '#475569';
        $textColor = $themeSettings['colors']['text'] ?? '#1e293b';
        $backgroundColor = $themeSettings['colors']['background'] ?? '#ffffff';

        $fontSize = $themeSettings['typography']['size'] ?? '14px';
        $fontFamily = $themeSettings['typography']['family'] ?? 'Inter';
        $lineHeight = $themeSettings['layout']['line_spacing'] ?? '1.5';
        $borderRadius = $themeSettings['layout']['border_radius'] ?? '0.375rem';
        $pageMargins = $themeSettings['layout']['margins'] ?? '10mm';

        $custom = "
            :root {
                --primary-color: {$primaryColor};
                --secondary-color: {$secondaryColor};
                --text-color: {$textColor};
                --bg-color: {$backgroundColor};
                --font-size: {$fontSize};
                --line-height: {$lineHeight};
                --border-radius: {$borderRadius};
            }
            @page {
                size: A4;
                margin: 0;
            }
            *, *::before, *::after {
                box-sizing: border-box;
            }
            body {
                font-family: '{$fontFamily}', sans-serif;
                font-size: var(--font-size);
                line-height: var(--line-height);
                color: var(--text-color);
                background-color: var(--bg-color);
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 0;
                padding: 0;
                overflow-x: hidden;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            img {
                max-width: 100%;
                height: auto;
            }
            .page-container, .cv-page {
                padding: {$pageMargins};
            }
            @media screen and (max-width: 600px) {
                body { font-size: calc(var(--font-size) * 0.85); }
                .page-container, .cv-page { padding: 5mm; }
                table, .two-column, .grid { width: 100% !important; }
            }
            /* ── GLOBAL CV HEIGHT FIX: No empty space below content ── */
            .cv-page, .cv-wrapper, .page-container, .resume-page {
                min-height: auto !important;
                height: auto !important;
                overflow: visible !important;
            }
            .sidebar, .left-col, .left-panel, .left-column {
                min-height: auto !important;
            }
            /* Allow content to flow naturally across pages */
            body {
                min-height: auto !important;
                height: auto !important;
                overflow: visible !important;
            }
            @media print {
                body { background: white !important; margin: 0; padding: 0; }
                .page-container, .cv-page, .cv-wrapper, .resume-page { 
                    box-shadow: none !important; 
                    margin: 0 !important; 
                    width: 100% !important; 
                    min-height: auto !important;
                    height: auto !important;
                }
                .page-break { page-break-after: always; break-after: page; }
                .no-break { page-break-inside: avoid; break-inside: avoid; }
            }
        ";

        return $custom . "\n" . ($css ?? '');
    }

    /**
     * Dynamically inject typography fonts, customized CSS, and Tailwind CDN.
     */
    private function getResponsiveMeta(): string
    {
        return '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">';
    }

    private function injectCssAndFonts(string $html, string $css, array $themeSettings): string
    {
        $fontFamily = $themeSettings['typography']['family'] ?? 'Inter';
        $fontsToLoad = array_unique(array_merge([$fontFamily], $this->extractGoogleFonts($html)));
        $fontSlugs = array_map(fn($f) => str_replace(' ', '+', $f), $fontsToLoad);
        $weights = '300;400;500;600;700;800;900';
        $fontImport = "<link href=\"https://fonts.googleapis.com/css2?family=" . implode('&family=', $fontSlugs) . ":wght@{$weights}&display=swap\" rel=\"stylesheet\">";

        $viewportMeta = '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">';

        $headStyles = "
            {$viewportMeta}
            {$fontImport}
            <style>
                {$css}
            </style>
        ";

        if (str_contains($html, '</head>')) {
            return str_replace('</head>', $headStyles . '</head>', $html);
        }

        $headTag = '<head>';
        if (str_contains($html, $headTag)) {
            return str_replace($headTag, $headTag . $headStyles, $html);
        }

        return "<html><head>{$headStyles}</head><body>{$html}</body></html>";
    }

    /**
     * Extract Google Font family names referenced in template HTML/CSS.
     */
    private function extractGoogleFonts(string $html): array
    {
        $fonts = [];
        if (preg_match_all('/font-family:\s*[\'"]?([A-Z][a-zA-Z\s]+?)[\'"]?\s*[;,]/i', $html, $matches)) {
            foreach ($matches[1] as $f) {
                $f = trim($f);
                if (!in_array($f, ['sans-serif', 'serif', 'monospace', 'cursive', 'system-ui', 'Arial', 'Helvetica', 'Georgia', 'Times', 'Courier'])) {
                    $fonts[] = $f;
                }
            }
        }
        return array_unique($fonts);
    }

    /**
     * Clean error payload rendering to avoid split preview crash.
     */
    private function errorFallback(string $message): string
    {
        $safeMessage = htmlspecialchars($message);
        return "
        <div style=\"padding: 24px; font-family: sans-serif; color: #b91c1c; background: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; margin: 20px; font-size: 14px; line-height: 1.6;\">
            <h4 style=\"margin: 0 0 8px 0; font-size: 16px; font-weight: bold;\">⚠️ Render Compilation Suspended</h4>
            <p style=\"margin: 0;\">{$safeMessage}</p>
            <hr style=\"border: none; border-top: 1px solid #fecaca; margin: 16px 0;\">
            <small style=\"color: #7f1d1d;\">Please correct your template Blade or HTML variables formatting in the template editor workspace.</small>
        </div>";
    }
}
