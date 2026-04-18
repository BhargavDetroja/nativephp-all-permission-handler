<?php

declare(strict_types=1);

use Nativephp\AllPermissionHandler\Support\IosInfoPlistMerger;

describe('IosInfoPlistMerger', function () {
    it('returns true without modifying file when string entries are empty', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'aph-plist-');
        $plist = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict/>
</plist>
XML;
        file_put_contents($tmp, $plist);
        $before = file_get_contents($tmp);

        expect(IosInfoPlistMerger::mergeFile($tmp, []))->toBeTrue();
        expect(file_get_contents($tmp))->toBe($before);

        unlink($tmp);
    });

    it('returns false when plist path is missing', function () {
        expect(IosInfoPlistMerger::mergeFile('/nonexistent/NativePHP-simulator-Info.plist', [
            'NSCameraUsageDescription' => 'Camera',
        ]))->toBeFalse();
    });

    it('appends new key and string to root dict', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'aph-plist-');
        file_put_contents($tmp, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict/>
</plist>
XML);

        expect(IosInfoPlistMerger::mergeFile($tmp, [
            'NSCameraUsageDescription' => 'Need camera for demo',
        ]))->toBeTrue();

        $dom = new DOMDocument();
        $dom->load($tmp);
        $xpath = new DOMXPath($dom);
        $keyNodes = $xpath->query("//dict/key[text()='NSCameraUsageDescription']");
        expect($keyNodes->length)->toBe(1);
        $stringNodes = $xpath->query("//dict/key[text()='NSCameraUsageDescription']/following-sibling::string[1]");
        expect($stringNodes->length)->toBe(1);
        expect(trim($stringNodes->item(0)?->textContent ?? ''))->toBe('Need camera for demo');

        unlink($tmp);
    });

    it('updates an existing string value for the same key', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'aph-plist-');
        file_put_contents($tmp, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>NSCameraUsageDescription</key>
	<string>Old text</string>
</dict>
</plist>
XML);

        expect(IosInfoPlistMerger::mergeFile($tmp, [
            'NSCameraUsageDescription' => 'New text',
        ]))->toBeTrue();

        $dom = new DOMDocument();
        $dom->load($tmp);
        $xpath = new DOMXPath($dom);
        $stringNodes = $xpath->query("//dict/key[text()='NSCameraUsageDescription']/following-sibling::string[1]");
        expect(trim($stringNodes->item(0)?->textContent ?? ''))->toBe('New text');

        unlink($tmp);
    });

    it('encodes special characters in string values safely', function () {
        $tmp = tempnam(sys_get_temp_dir(), 'aph-plist-');
        file_put_contents($tmp, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict/>
</plist>
XML);

        $value = 'Tom & Jerry <demo>';

        expect(IosInfoPlistMerger::mergeFile($tmp, [
            'NSCameraUsageDescription' => $value,
        ]))->toBeTrue();

        $dom = new DOMDocument();
        $dom->load($tmp);
        $xpath = new DOMXPath($dom);
        $stringNodes = $xpath->query("//dict/key[text()='NSCameraUsageDescription']/following-sibling::string[1]");
        expect($stringNodes->item(0)?->textContent)->toBe($value);

        unlink($tmp);
    });
});
