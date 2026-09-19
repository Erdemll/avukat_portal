# UYAP UDF Yapı Gözlemleri

Bu entegrasyon UDF dosyasını bir ZIP kapsayıcı olarak ele alır ve arşiv kökündeki
`content.xml` girdisini işler. UYAP UDF biçiminin herkese açık, eksiksiz ve sürümlü
bir şeması bulunmadığı için aşağıdaki yapı resmi bir spesifikasyon değildir.

## Doğrulama sınırı

- Adalet Bakanlığı kaynakları UYAP editörünün UDF/XML ve UTF-8 kullandığını doğrular.
- XML düğüm ve özellik eşlemeleri, açık kaynak UDF okuyucularının anonimleştirilmiş
  teknik gözlemleri esas alınarak oluşturulmuştur.
- Repoda gerçek müvekkil verisi içeren UDF tutulmaz. Açık kaynakta bulunan ve dosya
  adında kimlik numarası olabilecek veri taşıyan fixture projeye alınmamıştır.
- Testler sentetik ve anonim `content.xml` örneklerinden çalışma anında üretilen UDF
  arşivleriyle yapılır.
- Resmi UYAP Doküman Editörü ile manuel round-trip testi geçmeden tam UYAP uyumluluğu
  iddia edilmez.

Teknik dayanaklar:

- Adalet Bakanlığı, UYAP Doküman Editörü ve UDF/XML/UTF-8 açıklaması:
  <https://edb.adalet.gov.tr/Resimler/SayfaDokuman/266202015465619-B%C4%B0LG%C4%B0SAYAR%20VE%20UYAP%20B%C4%B0L%C4%B0%C5%9E%C4%B0M%20S%C4%B0STEM%C4%B0.pdf>
- Topluluk tarafından tersine mühendislikle belgelenmiş anonim teknik gözlemler:
  <https://github.com/saidsurucu/UDF-Toolkit/blob/main/Docs.md>

## ZIP gözlemleri

- Zorunlu girdi: arşiv kökündeki `content.xml` veya klasör ZIP'lendiğinde oluşan
  `klasor/content.xml`. Güvenli bir yolda yalnız bir adet `content.xml` bulunabilir.
- İmza göstergeleri farklı sürümlerde `sign.sgn`, `.p7s`, `.p7m` veya `META-INF`
  altında imza isimli girdiler olabilir.
- Düzenleme sırasında arşiv kopyalanır ve bulunan `content.xml` aynı arşiv yolunda
  değiştirilir; bilinmeyen girdiler korunur. Mevcut imza girdileri korunabilse de düzenlenen içerik için geçerli
  imza sayılmaz. Editörün oluşturduğu sürüm `signature_invalidated_by_edit` durumuyla
  gösterilir ve arayüz yeniden imza gerektirebileceğini açıkça bildirir.
- Arşiv diske açılmaz. Girdi adları Zip Slip, arşiv boyutu, toplam açılmış boyut,
  girdi sayısı ve XML boyutu limitlerinden geçirilir.

## `content.xml` gözlemleri

Beklenen kök:

```xml
<template format_id="1.8">
```

Bilinen ana düğümler:

- `content`: Belge metninin UTF-8 CDATA havuzu.
- `properties`: Sayfa biçimi gibi düzen bilgileri; serializer tarafından korunur.
- `elements`: Görüntülenebilir belge ağacı.
- `styles`: Stil tanımları; serializer tarafından korunur.
- `data`: Şablon verileri olabilir; serializer tarafından korunur.

`elements` altında desteklenen düğümler:

- `paragraph`
- `content`
- `tab` ve `space`
- `table`, `row`, `cell`
- `page-break` (editör modelinde yatay ayırıcı olarak temsil edilir)

Metin parçaları `startOffset` ve `length` ile CDATA havuzuna bağlanır. Offsetler bayt
değil Unicode karakter sayısı olarak hesaplanır. Türkçe karakterler bu nedenle UTF-8
bayt uzunluğuyla değil `mb_strlen`/`mb_substr` ile işlenir.

## Stil eşlemesi

| UDF | Canonical/Tiptap |
| --- | --- |
| `paragraph@Alignment=0/1/2/3` | `left/center/right/justify` |
| `paragraph@LeftIndent` | `paragraph.attrs.indent` |
| `content@bold=true` | `bold` mark |
| `content@italic=true` | `italic` mark |
| `content@underline=true` | `underline` mark |
| `content@size` | `fontSize` mark |
| `paragraph@Bulleted=true` | `bulletList` |
| `paragraph@Numbered=true` | `orderedList` |
| `page-break` | `horizontalRule` |

## Tablo eşlemesi

`table > row > cell` yapısı sırasıyla `table`, `tableRow`, `tableCell` düğümlerine
çevrilir. Hücre içindeki paragraflar korunur. `colspan` desteklenir; bilinmeyen tablo
özellikleri görüntüleme sırasında kısmi uyumluluk olarak raporlanır.

## Bilinmeyen düğümler ve uyumluluk

- `full`: Yalnız güvenli biçimde desteklenen yapılar bulunur.
- `partial`: İçeriği etkilemeyen bilinmeyen ana metadata/stil düğümleri vardır; özgün
  XML içinde korunur.
- `unsupported`: `elements` altında offset veya içerik anlamını değiştirebilecek
  bilinmeyen bir yapı vardır. Mümkün olan metin görüntülenir ancak düzenleme kapatılır.

Parser bilinmeyen düğüm adlarını kullanıcıya gösterilecek uyumluluk listesine ekler;
raw XML'i, belge içeriğini veya özel depolama yolunu loglamaz.

## Serializer varsayımları

- Yalnız sunucuda doğrulanmış canonical JSON kabul edilir.
- `properties`, `styles`, `data` ve tanınmayan arşiv girdileri korunur.
- Düzenlenebilir belgede `elements` ve metin havuzu birlikte yeniden oluşturulur.
- Varsayılan yazı tipi `Times New Roman`, varsayılan boyut `12` puntodur.
- Boş paragraf U+200B ile temsil edilir.
- Satır sonu, sekme ve paragraf ayrımı normalize edilmeden ayrı karakterler olarak
  işlenir.

## Manuel uyumluluk kontrolü

1. UYAP'tan anonim/test UDF alın.
2. Portala yükleyip görüntüleyin.
3. Metin ve biçimlendirmeyi değiştirip yeni sürüm kaydedin.
4. Yeni UDF'yi indirin.
5. Resmi UYAP Doküman Editörü ile açın.
6. Metin, paragraf, Türkçe karakter, liste, tablo ve biçimlendirmeyi karşılaştırın.
