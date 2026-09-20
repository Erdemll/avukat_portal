# TEPENET AVUKAT PORTALI

## Kullanım Kılavuzu ve Kullanıcı Eğitim Metni

**Doküman sürümü:** 1.0  
**Hazırlanma tarihi:** Eylül 2026  
**Hedef kullanıcılar:** Yönetici, Avukat ve Çalışan  
**Uygulama:** Tepenet Avukat Portalı

> PDF düzenleme notu: Köşeli parantez içinde belirtilen ekran görüntüsü alanları, ilgili uygulama ekranından alınacak görsellerle değiştirilebilir. Kuruma ait URL, destek e-posta adresi ve doküman sürümü PDF hazırlanırken güncellenmelidir.

---

## 1. Kılavuzun Amacı

Tepenet Avukat Portalı; hukuki taleplerin, dava ve icra dosyalarının, müvekkillerin, evrakların, duruşmaların, sürelerin, görevlerin ve diğer hukuk operasyonlarının tek merkezden yönetilmesini sağlar.

Bu kılavuzun amacı kullanıcıların:

- Sisteme güvenli şekilde giriş yapmasını,
- Rollerine uygun ekranları tanımasını,
- Hukuki talep ve dosya süreçlerini doğru yürütmesini,
- Evrak ve UDF belgelerini güvenli şekilde yönetmesini,
- Duruşma, süre, görev ve tebligat kayıtlarını takip etmesini,
- Bildirimleri, raporları ve yönetim araçlarını doğru kullanmasını

sağlamaktır.

Uygulamada görünen menüler ve işlem düğmeleri kullanıcının rolüne ve ilgili kayda erişim yetkisine göre değişebilir. Bu nedenle kılavuzdaki bazı ekranlar her kullanıcıda görünmeyebilir.

---

## 2. Kullanıcı Rolleri ve Yetki Özeti

### 2.1. Çalışan

Çalışan rolü, hukuki olay veya talep oluşturan birim kullanıcıları içindir.

Çalışan:

- Yeni hukuki talep oluşturabilir.
- Kendisinin oluşturduğu talepleri görüntüleyebilir.
- Kendi talebine belge ekleyebilir.
- Talebin atandığı avukatı, durumunu ve süreç geçmişini takip edebilir.
- Bildirimlerini ve güvenlik ayarlarını yönetebilir.

Çalışan; hukuki dosya, müvekkil, finans ve yönetim modüllerine erişemez.

### 2.2. Avukat

Avukat rolü, kendisine atanmış olay ve hukuki dosyaları yürüten kullanıcılar içindir.

Avukat:

- Kendisine atanmış hukuki talepleri görüntüleyebilir ve yönetebilir.
- Talebe süreç güncellemesi ve belge ekleyebilir.
- Atandığı hukuki dosyaları görüntüleyebilir ve güncelleyebilir.
- Yeni hukuki dosya ve müvekkil oluşturabilir.
- Dosya taraflarını, evraklarını ve hukuk operasyon kayıtlarını yönetebilir.
- Duruşma, süre, görev, tebligat, arabuluculuk ve müvekkil iletişimi kaydı oluşturabilir.
- Erişebildiği finans hareketlerini görüntüleyebilir.
- Dosya devir veya dosyaya atanma talebi oluşturabilir.
- Atandığı dosyalardaki desteklenen UDF belgelerini düzenleyebilir.

Avukat yalnızca aktif olarak atandığı dosyalara ve yetkisi kapsamındaki müvekkil kayıtlarına erişebilir.

### 2.3. Yönetici

Yönetici rolü sistemin operasyonel ve idari yönetiminden sorumludur.

Yönetici:

- Tüm hukuki talepleri, dosyaları ve operasyon kayıtlarını görüntüleyebilir.
- Olayları avukatlara atayabilir veya atanan avukatı değiştirebilir.
- Hukuki dosyalardaki avukat atamalarını yönetebilir.
- Finans hareketi ve ters kayıt oluşturabilir.
- Dosya devir ve atama taleplerini onaylayabilir veya reddedebilir.
- Raporları görüntüleyebilir ve CSV olarak dışa aktarabilir.
- UYAP CSV aktarımı yapabilir.
- Kullanıcıları, olay türlerini ve dosya türlerini yönetebilir.
- Audit kayıtlarını görüntüleyebilir.

### 2.4. Yetki Matrisi

| Özellik | Çalışan | Avukat | Yönetici |
|---|---:|---:|---:|
| Dashboard | Evet | Evet | Evet |
| Hukuki talep oluşturma | Evet | Hayır | Evet |
| Talep süreç güncellemesi | Hayır | Atandığı kayıtlarda | Tüm kayıtlarda |
| Hukuki dosyalar | Hayır | Atandığı dosyalarda | Tüm dosyalarda |
| Müvekkiller | Hayır | Yetkili olduğu kayıtlarda | Tüm kayıtlarda |
| Duruşma, süre ve görevler | Hayır | Yetkili olduğu dosyalarda | Tüm dosyalarda |
| Finans hareketi görüntüleme | Hayır | Yetkili olduğu dosyalarda | Tüm dosyalarda |
| Finans hareketi oluşturma / ters kayıt | Hayır | Hayır | Evet |
| Dosya devir / atama talebi oluşturma | Hayır | Evet | Hayır |
| Devir / atama talebi kararı | Hayır | Hayır | Evet |
| UDF görüntüleme | Hayır | Yetkili olduğu dosyalarda | Tüm dosyalarda |
| UDF düzenleme | Hayır | Yetkili olduğu dosyalarda | Hayır |
| Rapor ve UYAP aktarımı | Hayır | Hayır | Evet |
| Kullanıcı ve tanım yönetimi | Hayır | Hayır | Evet |
| Audit kayıtları | Hayır | Hayır | Evet |

> Önemli: Bir kullanıcının rolü bir modüle genel erişim sağlayabilir; ancak görüntülenebilen kayıtlar ayrıca dosya ataması, kayıt sahibi veya ilgili taraf ilişkisine göre sınırlandırılır.

---

## 3. Sisteme Giriş

### 3.1. Yönetici veya Çalışan Girişi

1. Uygulamanın giriş sayfasını açın.
2. **Yönetici / Çalışan** sekmesini seçin.
3. Kurumsal e-posta adresinizi girin.
4. Şifrenizi girin.
5. Kişisel ve güvenli bir cihaz kullanıyorsanız isteğe bağlı olarak **Bu cihazda oturumu açık tut** seçeneğini işaretleyin.
6. **Güvenli giriş yap** düğmesine basın.

### 3.2. Avukat Girişi

1. Giriş ekranında **Avukat** sekmesini seçin.
2. Sistemde tanımlı 11 haneli sicil/T.C. kimlik numaranızı girin.
3. Şifrenizi girin.
4. **Güvenli giriş yap** düğmesine basın.

Kimlik veya şifre bilgilerinin art arda hatalı girilmesi halinde güvenlik amacıyla geçici giriş kısıtlaması uygulanabilir.

### 3.3. Şifremi Unuttum

1. Giriş ekranındaki **Şifremi unuttum** bağlantısına basın.
2. Sistemde kayıtlı e-posta adresinizi girin.
3. E-postanıza gönderilen şifre sıfırlama bağlantısını açın.
4. Yeni şifrenizi belirleyin ve işlemi tamamlayın.

E-posta ulaşmazsa spam/gereksiz klasörünü kontrol edin ve sistem yöneticinizle iletişime geçin.

### 3.4. İki Aşamalı Doğrulama

Hesabınızda iki aşamalı doğrulama açıksa şifre kontrolünden sonra e-posta adresinize süreli bir doğrulama kodu gönderilir.

1. E-postadaki kodu doğrulama ekranına girin.
2. Kod gelmediyse kısa bir süre bekleyin ve **Tekrar gönder** seçeneğini kullanın.
3. Kodu başka kişilerle paylaşmayın.

**[Ekran görüntüsü 1: Giriş ekranı]**  
**[Ekran görüntüsü 2: İki aşamalı doğrulama ekranı]**

---

## 4. Uygulama Arayüzü

### 4.1. Masaüstü Menü

Masaüstünde ana menü ekranın sol tarafında bulunur. Menü, kullanıcının rolüne göre şu grupları gösterebilir:

- **Çalışma Alanı:** Genel Bakış, Hukuki Talepler, Hukuki Dosyalar, Müvekkiller ve Gelişmiş Arama.
- **Hukuk Operasyonu:** Evraklar, Hukuki Takvim, Duruşmalar, Süreler, Görevler, Tebligatlar, Arabuluculuk, Finans Hareketleri, Müvekkil İletişimleri ve Dosya Talepleri.
- **Yönetim:** Raporlar, UYAP Aktarımı, Kullanıcılar, Olay Türleri, Dosya Türleri ve Audit Kayıtları.

### 4.2. Mobil Menü

Telefon ve tabletlerde sağ üstteki menü simgesine dokunun. Açılan menü ekranın tamamını kaplar ve içerik dikey olarak kaydırılabilir. Menüyü kapatmak için sağ üstteki kapatma simgesine dokunun.

### 4.3. Bildirim Alanı

Üst menüdeki zil simgesi okunmamış bildirim sayısını gösterir.

- Bildirime dokunmak ilgili kaydı açar ve bildirimi okunmuş kabul eder.
- **Tümünü gör** bağlantısı bildirim listesini açar.
- Bildirimler ekranında tek bir bildirimi veya tüm bildirimleri okunmuş olarak işaretleyebilirsiniz.

### 4.4. Çıkış

Ortak veya başka kişilerin erişebileceği cihazlarda işiniz bittiğinde mutlaka **Çıkış Yap** seçeneğini kullanın. Yalnızca tarayıcı sekmesini kapatmak güvenli çıkış anlamına gelmez.

---

## 5. Dashboard – Genel Bakış

Dashboard, kullanıcının erişebildiği hukuki taleplerin genel durumunu gösterir.

Gösterilen sayaçlar:

- Toplam veya kullanıcıya atanan talep sayısı,
- Açık,
- Devam eden,
- Bekleyen,
- Çözülen,
- Kapalı,
- Acil öncelikli talepler.

Alt bölümlerde son oluşturulan veya güncellenen olaylar yer alır.

Avukatlar ayrıca henüz süreç güncellemesi girilmemiş açık talepleri görebilir. Yöneticiler son süreç güncellemelerini ve avukat iş yükü özetini görüntüleyebilir.

**[Ekran görüntüsü 3: Dashboard genel görünümü]**

---

## 6. Hukuki Talepler

Hukuki Talepler modülü, çalışan birimlerden hukuk ekibine iletilen olay ve taleplerin yönetildiği alandır.

### 6.1. Talepleri Listeleme ve Filtreleme

**Çalışma Alanı > Hukuki Talepler** menüsünü açın.

Liste aşağıdaki alanlara göre filtrelenebilir:

- Talep numarası, başlık, oluşturan veya avukat adı,
- Olay türü,
- Durum,
- Öncelik,
- Atanan avukat,
- Oluşturan kullanıcı,
- Başlangıç ve bitiş tarihi.

Liste; talep numarası, oluşturulma tarihi, güncellenme tarihi veya önceliğe göre sıralanabilir.

### 6.2. Yeni Hukuki Talep Oluşturma

Bu işlem Çalışan ve Yönetici rolleri tarafından yapılabilir.

1. **Yeni Olay Oluştur** düğmesine basın.
2. Olay türünü seçin.
3. Talebin atanacağı avukatı seçin.
4. Açıklayıcı bir başlık yazın.
5. Talebin ayrıntılarını açıklama alanına girin.
6. Önceliği belirleyin: Düşük, Normal, Yüksek veya Acil.
7. Varsa oluşma tarihini ve mevcut süreç bilgisini girin.
8. Kaydedin.

Kayıt oluşturulduğunda sistem benzersiz bir talep numarası üretir ve atanan avukata bildirim gönderir.

### 6.3. Talep Detayı

Talep detay ekranında şu bilgiler bulunur:

- Talep numarası ve başlığı,
- Olay türü,
- Oluşturan kullanıcı,
- Atanan avukat,
- Durum ve öncelik,
- Mevcut süreç açıklaması,
- Ekli belgeler,
- Süreç geçmişi,
- Bağlı hukuki dosyalar.

### 6.4. Belge Ekleme

Yetkili kullanıcılar talebe aynı anda en fazla 10 belge yükleyebilir. Her dosya en fazla 50 MB olabilir. PDF, Office belgeleri, yaygın resim, ses, video ve metin biçimleri desteklenir.

Belge yüklemeden önce dosya adının içeriği doğru tanımladığından ve gereksiz kişisel veri içermediğinden emin olun.

### 6.5. Süreç Güncellemesi Ekleme

Yönetici veya talebe atanmış avukat:

1. Talep detayındaki **Süreç Geçmişi** bölümüne gider.
2. Kısa bir başlık girer.
3. Gerekliyse savcılık/mahkeme bilgisini yazar.
4. Yapılan işlem ve gelişmeleri açıklar.
5. İsteğe bağlı belge ekler.
6. Güncellemeyi kaydeder.

Süreç kayıtlarında kısa ve belirsiz ifadeler yerine yapılan işlemi, tarihi ve sonraki adımı açıklayan metinler kullanılmalıdır.

### 6.6. Durum ve Öncelik Yönetimi

Talep durumları:

- Açık,
- Devam Ediyor,
- Beklemede,
- Çözüldü,
- Kapalı.

Öncelik seviyeleri:

- Düşük,
- Normal,
- Yüksek,
- Acil.

Yönetici veya atanmış avukat, talep detayındaki yönetim alanından durum ve önceliği güncelleyebilir. Atanan avukatı yalnızca Yönetici değiştirebilir.

### 6.7. Talebi Hukuki Dosyaya Dönüştürme

Yönetici veya talebe atanmış avukat, uygun bir talebi hukuki dosyaya dönüştürebilir.

1. Talep detayındaki **Hukuki Dosyaya Dönüştür** seçeneğini açın.
2. Talep başlığı, açıklaması ve önceliği otomatik olarak forma aktarılır.
3. Dosya türü, müvekkil, avukat ve mahkeme/icra bilgilerini tamamlayın.
4. Kaydedin.

Aynı talep yalnızca bir kez kaynak dosya olarak dönüştürülebilir. Oluşturulan hukuki dosya ile kaynak talep arasındaki ilişki korunur.

**[Ekran görüntüsü 4: Hukuki talepler listesi]**  
**[Ekran görüntüsü 5: Talep detay ve süreç geçmişi]**

---

## 7. Hukuki Dosyalar

### 7.1. Dosya Listesi

**Çalışma Alanı > Hukuki Dosyalar** menüsünü açın.

Dosyalar aşağıdaki ölçütlerle filtrelenebilir:

- Dosya numarası, başlık veya taraf,
- Dosya türü,
- Atanan avukat,
- Durum veya ana kategori.

Avukat yalnızca aktif olarak atandığı dosyaları, Yönetici ise tüm dosyaları görür.

### 7.2. Yeni Hukuki Dosya Oluşturma

1. **Yeni Hukuki Dosya** düğmesine basın.
2. Dosya başlığını yazın.
3. Dosya türünü seçin.
4. Açılış tarihini ve önceliği belirleyin.
5. Dosya açıklamasını girin.
6. Yöneticiyseniz dosyaya atanacak avukatları ve lider avukatı seçin.
7. Varsa müvekkilleri seçin.
8. Mahkeme/icra bilgilerini girin:
   - Süreç türü,
   - Adliye,
   - Mahkeme veya icra müdürlüğü,
   - Esas yılı ve numarası,
   - Karar yılı ve numarası,
   - Harici dosya numarası,
   - Mahkeme türü.
9. Kaydedin.

Avukat tarafından oluşturulan dosyada oluşturan avukat otomatik olarak lider avukat atanır. Sistem dosya numarasını otomatik üretir.

### 7.3. Dosya Detay Ekranı

Dosya detay ekranında şu bölümler bulunur:

- Genel Bilgiler,
- Mahkeme / İcra Süreci,
- Taraflar,
- Evraklar,
- Duruşmalar,
- Hukuki Süreler,
- Görevler,
- Tebligatlar,
- Arabuluculuk,
- Finans,
- Müvekkil İletişimleri,
- Sorumlu Avukatlar,
- Durum geçmişi.

Üstteki sekme bağlantılarıyla ilgili bölüme hızlıca gidilebilir.

### 7.4. Dosya Durumları

Hukuki dosya durumları:

- **Aktif:** İşlemler devam ediyor.
- **Beklemede:** Dosya dış bir işlem veya gelişme bekliyor.
- **Sonuçlandı:** Esas süreç tamamlandı.
- **Kapalı:** Dosya arşiv niteliğinde kapatıldı.

İzin verilen temel geçişler:

- Aktif → Beklemede veya Sonuçlandı,
- Beklemede → Aktif veya Sonuçlandı,
- Sonuçlandı → Aktif veya Kapalı,
- Kapalı → Aktif.

Kapalı bir dosyayı yalnızca Yönetici yeniden açabilir. Durum değiştirirken gerekçe alanına açıklayıcı bilgi girilmelidir.

### 7.5. Avukat Atamaları

Avukat atamalarını yalnızca Yönetici değiştirebilir.

1. Dosya detayındaki **Avukat Atamaları** bölümünü açın.
2. Dosyada görev alacak avukatları seçin.
3. Bir lider avukat belirleyin.
4. Atama veya değişiklik gerekçesini yazın.
5. **Atamaları Güncelle** düğmesine basın.

Ataması sona eren avukat dosyaya erişimini kaybedebilir. Bu nedenle atama değişikliğinden önce açık görevler ve yaklaşan tarihler kontrol edilmelidir.

### 7.6. Taraf Ekleme

Dosyaya mevcut bir taraf eklenebilir veya yeni taraf oluşturulabilir.

Taraf rolü seçenekleri:

- Müvekkil,
- Davacı,
- Davalı,
- Alacaklı,
- Borçlu,
- Diğer.

Taraf yönü seçenekleri:

- Bizim Taraf,
- Karşı Taraf,
- Tarafsız / Diğer.

Yanlış taraf ilişkisi oluşturulduysa **İlişkiyi Sonlandır** seçeneği kullanılabilir. Bu işlem kişi veya şirket kaydını silmez; yalnızca dosya ilişkisini sona erdirir.

**[Ekran görüntüsü 6: Hukuki dosya oluşturma formu]**  
**[Ekran görüntüsü 7: Hukuki dosya detay ekranı]**

---

## 8. Müvekkil Yönetimi

### 8.1. Müvekkil Listesi

**Çalışma Alanı > Müvekkiller** menüsünü açın. Ad, soyad veya şirket unvanına göre arama yapılabilir; aktif ve pasif kayıtlar filtrelenebilir.

### 8.2. Yeni Müvekkil Oluşturma

1. **Yeni Müvekkil** düğmesine basın.
2. Kişi türünü seçin:
   - Gerçek Kişi,
   - Tüzel Kişi.
3. Gerçek kişi için ad ve soyad; tüzel kişi için şirket unvanını girin.
4. Telefon, e-posta ve adres bilgilerini girin.
5. Gerekliyse kimlik türünü seçin:
   - T.C. Kimlik No,
   - Vergi No,
   - Diğer.
6. Müvekkillik başlangıç tarihini girin.
7. İletişim notlarını ve müvekkil notlarını ekleyin.
8. Kaydedin.

Hassas kimlik bilgileri yalnızca Yönetici tarafından görüntülenebilir. Bu alanlara yalnızca gerçekten gerekli bilgiler girilmelidir.

### 8.3. Müvekkil Detayı

Müvekkil detayında iletişim bilgileri, bağlı hukuki dosyalar ve iletişim geçmişi görüntülenir. Yeni dosya veya iletişim kaydı bu ekrandan başlatılabilir.

Müvekkil kayıtları silinmez; kullanılmayan kayıtlar **Pasif** duruma alınır.

**[Ekran görüntüsü 8: Müvekkil listesi ve detay ekranı]**

---

## 9. Evrak ve Belge Yönetimi

### 9.1. Evrak Listesi

**Hukuk Operasyonu > Evraklar** menüsünde erişebildiğiniz dosyalardaki belgeler yer alır.

Evraklar şu alanlarla filtrelenebilir:

- Başlık, dosya adı veya dosya numarası,
- Hukuki dosya,
- Sanal klasör,
- Evrak türü.

Evrak türleri:

- Dilekçe,
- Delil,
- Tebligat,
- Rapor,
- Sözleşme,
- Fatura,
- İcra Evrakı,
- Diğer.

### 9.2. Dosyaya Evrak Yükleme

1. İlgili hukuki dosyayı açın.
2. **Evraklar** bölümüne gidin.
3. Sanal klasör ve evrak türünü seçin.
4. İsteğe bağlı evrak başlığı girin.
5. Dosyaları seçin.
6. **Evrak Yükle** düğmesine basın.

Aynı işlemde en fazla 10 dosya yüklenebilir. Her dosya en fazla 50 MB olabilir. Hukuki dosyalarda PDF, DOC/DOCX, XLS/XLSX, JPG/JPEG, PNG ve UDF biçimleri desteklenir.

Yeni hukuki dosyalarda şu varsayılan klasörler otomatik oluşturulur:

- Dilekçeler,
- Tebligatlar,
- Duruşma Tutanakları,
- Bilirkişi Raporları,
- Deliller,
- Sözleşmeler,
- Faturalar,
- İcra Evrakları,
- Diğer.

Gerekirse dosyaya özel yeni sanal klasör oluşturulabilir. Kapalı dosyada yeni klasör oluşturulamaz.

### 9.3. Önizleme ve İndirme

PDF, JPEG ve PNG belgeleri tarayıcıda önizlenebilir. Diğer desteklenen dosyalar indirilebilir. Belge bağlantılarının yetkisiz kişilerle paylaşılmaması gerekir.

### 9.4. Yeni Belge Sürümü Yükleme

Mevcut bir belgenin güncellenmiş kopyası **Yeni Sürüm** alanından yüklenir.

1. Yeni dosyayı seçin.
2. Değişikliğin nedenini açıklayan bir sürüm notu yazın.
3. **Yeni Sürüm** düğmesine basın.

Eski sürüm silinmez. Sürüm geçmişinden önceki sürümler görüntülenebilir veya indirilebilir. Böylece belge geçmişi korunur.

### 9.5. Belge Silme

Belge silme/arşivleme yetkisi yalnızca Yöneticiye aittir. İşlem öncesinde belgenin doğru dosyaya ait olduğu mutlaka kontrol edilmelidir.

**[Ekran görüntüsü 9: Evrak listesi ve sürüm geçmişi]**

---

## 10. UYAP UDF Belgeleri

### 10.1. UDF Yükleme

UDF dosyası yüklerken:

- Dosya uzantısı yalnızca `.udf` olmalıdır; `.udf.zip` kullanılmamalıdır.
- UDF, ZIP tabanlı bir arşiv olmalı ve güvenli bir konumda yalnızca bir adet `content.xml` içermelidir.
- Desteklenmeyen veya bozuk XML yapıları yükleme sırasında reddedilebilir.
- Belge yükleme ekranındaki genel dosya sınırı 50 MB olsa da UDF arşivlerine daha düşük güvenlik sınırları uygulanabilir. Büyük veya olağan dışı içeriklere sahip arşivler reddedilebilir.

### 10.2. UDF Görüntüleme

UDF detay ekranında:

- Bağlı hukuki dosya,
- Sürüm numarası,
- Yükleyen kullanıcı,
- Yükleme tarihi,
- İmza göstergesi,
- Uyumluluk durumu

görüntülenir.

Uyumluluk durumları:

- **Tam:** Belge yapısı destekleniyor.
- **Kısmi:** Belge açılmıştır ancak bazı yapılar tam desteklenmeyebilir.
- **Desteklenmiyor:** Belge güvenli biçimde düzenlenemez; yalnızca indirme seçeneği kullanılmalıdır.

### 10.3. UDF Düzenleme

UDF düzenleme yalnızca dosyaya erişimi olan Avukat tarafından yapılabilir.

Editör araçları:

- Geri al ve yinele,
- Kalın, italik ve altı çizili metin,
- Yazı boyutu,
- Sola, ortaya, sağa ve iki yana hizalama,
- Madde işaretli ve numaralı liste,
- Girinti artırma ve azaltma,
- 2 × 2 tablo,
- Yatay ayırıcı.

Değişiklik tamamlandıktan sonra **Yeni Sürüm Olarak Kaydet** düğmesine basın. Orijinal UDF sürümü korunur; düzenlenen belge yeni bir sürüm olarak kaydedilir.

### 10.4. Elektronik İmza Uyarısı

Portal UDF içindeki imza göstergelerini algılayabilir ancak elektronik imzanın hukuki veya kriptografik geçerliliğini doğrulamaz.

İmzalı olabilecek bir UDF düzenlendiğinde:

- Eski sürüm korunur.
- Yeni sürümde mevcut imza geçerli kabul edilmez.
- Belgenin yeniden elektronik imzalanması gerekebilir.
- Resmî işlem öncesinde belge UYAP Doküman Editörü ile ayrıca kontrol edilmelidir.

**[Ekran görüntüsü 10: UDF görüntüleme ekranı]**  
**[Ekran görüntüsü 11: UDF düzenleyici]**

---

## 11. Hukuki Takvim

**Hukuk Operasyonu > Hukuki Takvim** ekranı şu kayıtları tek kronolojik akışta gösterir:

- Duruşmalar,
- Hukuki süreler,
- Son tarihi bulunan görevler.

Başlangıç ve bitiş tarihini seçerek takvim aralığını değiştirebilirsiniz. Tarih aralığı bir yıldan uzun olamaz. Her kayda dokunarak ilgili düzenleme ekranına gidilebilir.

**[Ekran görüntüsü 12: Hukuki takvim]**

---

## 12. Duruşmalar

### 12.1. Yeni Duruşma

1. **Hukuk Operasyonu > Duruşmalar** menüsünü açın.
2. **Yeni Duruşma** düğmesine basın.
3. Hukuki dosyayı ve duruşma avukatını seçin.
4. Başlık, mahkeme ve duruşma türünü girin.
5. Duruşma tarihini belirleyin.
6. Varsa sonraki duruşma tarihini girin.
7. Durumu, açıklamayı ve sonucu kaydedin.

Duruşma durumları:

- Planlandı,
- Tamamlandı,
- Ertelendi,
- İptal.

Planlanmış duruşmalar için yapılandırılmış süre içinde sorumlu avukata sistem bildirimi gönderilebilir.

---

## 13. Hukuki Süreler

### 13.1. Yeni Süre Kaydı

1. **Hukuk Operasyonu > Süreler** menüsünü açın.
2. **Yeni Süre** düğmesine basın.
3. Hukuki dosyayı ve sorumlu avukatı seçin.
4. Başlığı yazın.
5. Başlangıç ve son tarihi girin.
6. Durumu ve açıklamayı kaydedin.

Süre durumları:

- Açık,
- Tamamlandı,
- İptal.

Gecikmiş açık süreler raporlarda ayrıca gösterilir. Açık süreler için yaklaşan son tarihe göre hatırlatma bildirimi üretilebilir.

---

## 14. Görevler

### 14.1. Yeni Görev

1. **Hukuk Operasyonu > Görevler** menüsünü açın.
2. **Yeni Görev** düğmesine basın.
3. Görevi bir hukuki dosyaya bağlayın veya **Kişisel görev** seçeneğini kullanın.
4. Atanacak kullanıcıyı seçin.
5. Başlık, öncelik, son tarih, durum ve açıklamayı girin.
6. Kaydedin.

Görev durumları:

- Bekliyor,
- Devam Ediyor,
- Tamamlandı,
- İptal.

Avukat, kendisine atanan veya kendisinin oluşturduğu kişisel görevleri yönetebilir. Yönetici tüm görevleri yönetebilir.

---

## 15. Tebligatlar

### 15.1. Yeni Tebligat

1. **Hukuk Operasyonu > Tebligatlar** menüsünü açın.
2. **Yeni Tebligat** düğmesine basın.
3. Hukuki dosyayı seçin.
4. Tebligat türünü seçin:
   - Gelen Tebligat,
   - Giden Tebligat,
   - Mahkeme Tebligatı,
   - İcra Tebligatı,
   - Diğer.
5. Gönderen ve alıcı bilgisini girin.
6. Bildirim ve tebliğ tarihlerini girin.
7. Varsa bağlı evrakı seçin.
8. Açıklama ekleyip kaydedin.

Yeni tebligatlar ilgili dosyanın avukatlarına bildirim oluşturabilir.

---

## 16. Arabuluculuk

### 16.1. Yeni Arabuluculuk Kaydı

1. **Hukuk Operasyonu > Arabuluculuk** menüsünü açın.
2. **Yeni Kayıt** düğmesine basın.
3. Hukuki dosyayı seçin.
4. Arabuluculuk dosya numarasını ve arabulucu adını girin.
5. Başvuru, toplantı ve varsa tamamlanma tarihini girin.
6. Durumu seçin:
   - Devam Ediyor,
   - Anlaşma,
   - Anlaşamama.
7. Sonucu açıklayıp kaydedin.

---

## 17. Müvekkil İletişimleri

Telefon görüşmesi, e-posta, toplantı ve mesaj kayıtları bu modülde tutulur.

### 17.1. Yeni İletişim Kaydı

1. **Hukuk Operasyonu > Müvekkil İletişimleri** menüsünü açın.
2. **Yeni İletişim** düğmesine basın.
3. Müvekkili seçin.
4. İletişimi ilgili hukuki dosyaya bağlayın veya dosyasız bırakın.
5. İletişim türünü seçin:
   - Telefon,
   - E-posta,
   - Toplantı,
   - Mesaj.
6. Tarih, konu ve açıklamayı girin.
7. Kaydedin.

İletişim açıklamasında görüşmenin özeti, alınan kararlar ve takip edilmesi gereken sonraki adımlar belirtilmelidir.

---

## 18. Finans Hareketleri

Bu bölüm muhasebe sistemi yerine hukuki dosya bazlı alacak, tahsilat/ödeme ve masraf takibi için kullanılır.

### 18.1. Finans Hareketlerini Görüntüleme

Avukat, erişebildiği dosyaların finans hareketlerini görebilir. Para birimi bazında alacak, tahsilat/ödeme ve masraf özetleri listelenir.

### 18.2. Yeni Finans Hareketi

Yalnızca Yönetici:

1. **Hukuk Operasyonu > Finans Hareketleri** menüsünü açar.
2. Hukuki dosyayı seçer.
3. Hareket türünü seçer: Alacak, Tahsilat/Ödeme veya Masraf.
4. Tutar ve para birimini girer.
5. İşlem tarihini ve açıklamayı yazar.
6. **Finans Hareketi Ekle** düğmesine basar.

### 18.3. Ters Kayıt

Finans hareketleri değiştirilemez veya silinemez. Hatalı bir hareket için Yönetici **Ters Kayıt** oluşturmalıdır.

1. İlgili hareketin ters kayıt alanına gerekçeyi yazın.
2. **Ters Kayıt** düğmesine basın.
3. Sistem aynı tutarda, asıl kayıtla bağlantılı ayrı bir ters kayıt oluşturur.

Aynı hareket yalnızca bir kez ters kaydedilebilir.

**[Ekran görüntüsü 13: Finans hareketleri ekranı]**

---

## 19. Dosya Devir ve Atama Talepleri

Bu modül avukatların dosya atamalarını yönetici onayına sunmasını sağlar.

### 19.1. Dosya Devir Talebi

Avukat yalnızca aktif olarak atandığı bir dosya için devir talebi oluşturabilir.

1. **Hukuk Operasyonu > Dosya Talepleri** ekranını açın.
2. **Dosya Devir Talebi** bölümünde dosyayı seçin.
3. Hedef avukatı seçin.
4. Devir gerekçesini yazın.
5. Talebi gönderin.

### 19.2. Dosya Atama Talebi

Avukat, henüz atanmadığı açık bir dosyaya atanmak için talep oluşturabilir.

1. Dosya numarasını eksiksiz girin.
2. Atama gerekçesini yazın.
3. Talebi gönderin.

Gizlilik amacıyla erişiminiz olmayan dosyaların ayrıntıları bu ekranda listelenmez.

### 19.3. Talebi İptal Etme

Talebi oluşturan avukat, talep henüz beklemedeyken iptal edebilir.

### 19.4. Yönetici Kararı

Yönetici bekleyen talebi açarak:

- Onaylayabilir,
- Reddedebilir,
- Devir için hedef avukatı değiştirebilir,
- Gerekiyorsa yeni avukatı lider avukat yapabilir,
- Karar notu ekleyebilir.

Kapalı dosyaya ait talepler onaylanamaz. Sonuç, talebi oluşturan ve ilgili kullanıcılar için bildirim üretir.

**[Ekran görüntüsü 14: Dosya devir ve atama talepleri]**

---

## 20. Gelişmiş Arama

Gelişmiş Arama; erişim yetkiniz bulunan kayıtlar içinde tek noktadan arama yapar.

Aranabilen kayıtlar:

- Hukuki dosyalar,
- Evraklar,
- Müvekkiller,
- Duruşmalar,
- Hukuki süreler,
- Görevler.

Arama örnekleri:

- Dosya numarası,
- Dosya veya görev başlığı,
- Kişi veya şirket adı,
- Evrak adı,
- Mahkeme veya icra müdürlüğü,
- Esas numarası,
- Telefon veya e-posta.

Takvim kayıtlarında başlangıç ve bitiş tarihi filtresi kullanılabilir. Arama sonuçları kullanıcının erişim yetkisiyle sınırlandırılır.

---

## 21. Bildirimler ve Hatırlatmalar

Sistem aşağıdaki işlemler için bildirim oluşturabilir:

- Hukuki talebin avukata atanması,
- Talebin güncellenmesi veya kapatılması,
- Hukuki dosyaya avukat atanması,
- Dosyaya belge yüklenmesi,
- Yeni finans hareketi,
- Yeni tebligat,
- Dosya devir/atama talebinin oluşturulması veya sonuçlandırılması,
- Yaklaşan duruşma,
- Yaklaşan hukuki süre.

Bildirim metnine dokunulduğunda ilgili kayıt açılır. Bir bildirimi **Okundu** olarak işaretleyebilir veya **Tümünü Okundu İşaretle** seçeneğini kullanabilirsiniz.

Bildirim, kullanıcı sorumluluğunu ortadan kaldırmaz. Duruşma ve sürelerin ayrıca düzenli olarak Hukuki Takvim üzerinden kontrol edilmesi gerekir.

---

## 22. Raporlar

Raporlar yalnızca Yönetici tarafından kullanılabilir.

### 22.1. Hukuk Operasyon Raporu

1. **Yönetim > Raporlar** menüsünü açın.
2. Başlangıç ve bitiş tarihini seçin.
3. **Uygula** düğmesine basın.

Raporda:

- Dosya durum sayıları,
- Dosya türlerine göre açılan dosyalar,
- Para birimi ve işlem türüne göre finans toplamları,
- Gecikmiş açık süre sayısı,
- Önümüzdeki 30 gündeki planlanmış duruşmalar

gösterilir.

Dosya açılışları ve finans hareketleri seçilen tarih aralığına göre hesaplanır. Gecikmiş süre ve 30 günlük duruşma sayaçları raporun görüntülendiği güne göre hesaplanır.

### 22.2. CSV Dışa Aktarma

**CSV İndir** seçeneğiyle rapor UTF-8 ve noktalı virgül ayrımlı CSV dosyası olarak indirilebilir. Dosya Excel veya benzeri tablo uygulamalarında açılabilir.

**[Ekran görüntüsü 15: Yönetim raporu]**

---

## 23. UYAP Manuel CSV Aktarımı

Bu özellik yalnızca Yönetici tarafından kullanılır ve UYAP kaynaklı kayıtların kontrollü biçimde hukuki dosyaya dönüştürülmesini sağlar.

### 23.1. Dosya Kuralları

- Dosya UTF-8 kodlamasında olmalıdır.
- CSV veya TXT uzantısı kullanılabilir.
- Dosya en fazla 2 MB ve 500 veri satırı olabilir.
- Virgül veya noktalı virgül ayıracı kullanılabilir.
- UYAP referansı daha önce aktarılmış olmamalıdır.

Zorunlu başlıklar:

```text
uyap_referans
dosya_basligi
dosya_turu
oncelik
acilis_tarihi
avukat_eposta
yargi_turu
mahkeme
esas_yili
esas_no
```

Alan kuralları:

- `oncelik`: low, normal, high veya urgent,
- `acilis_tarihi`: YYYY-AA-GG,
- `yargi_turu`: dava, icra, arabuluculuk veya diğer,
- `dosya_turu`: sistemdeki aktif dosya türünün adı veya slug değeri,
- `avukat_eposta`: sistemdeki aktif bir avukatın e-posta adresi.

### 23.2. Önizleme ve Aktarım

1. **Yönetim > UYAP Aktarımı** menüsünü açın.
2. CSV dosyasını seçin.
3. Önce **Doğrula ve önizle** seçeneğini kullanın.
4. Hatalı satırları kaynak CSV içinde düzeltin.
5. Düzeltilmiş dosyayı yeniden seçin.
6. **Geçerliyse içe aktar** düğmesine basın.

Herhangi bir satır hatalıysa aktarım yapılmaz. Tüm satırlar geçerliyse kayıtların tamamı tek işlem içinde oluşturulur. Aktarılan dosyalarda ilgili avukat lider avukat olarak atanır ve UYAP referansı harici dosya numarası olarak kaydedilir.

**[Ekran görüntüsü 16: UYAP CSV doğrulama ve önizleme]**

---

## 24. Yönetim İşlemleri

### 24.1. Kullanıcı Yönetimi

**Yönetim > Kullanıcılar** ekranında Yönetici:

- Kullanıcıları listeler,
- Yeni kullanıcı oluşturur,
- Ad, e-posta, telefon ve rol bilgilerini günceller,
- Hesabı aktif veya pasif yapar,
- Şifre oluşturma/sıfırlama e-postası gönderir.

Avukat rolü seçildiğinde 11 haneli T.C. kimlik/sicil numarası zorunludur.

Yeni kullanıcıya sistem içinde doğrudan şifre verilmez. Kullanıcı oluşturulduktan sonra **Şifre Sıfırlama Gönder** seçeneğiyle güvenli bağlantı gönderilmelidir.

Güvenlik kuralları:

- Yönetici kendi hesabını pasifleştiremez.
- Sistemde en az bir aktif Yönetici kalmalıdır.
- Aktif olay veya hukuki dosyası bulunan Avukat pasifleştirilemez ve rolü doğrudan değiştirilemez. Önce aktif işleri başka avukata devredilmelidir.

### 24.2. Olay Türleri

**Yönetim > Olay Türleri** ekranında yeni talep kategorileri oluşturulur.

Alanlar:

- Ad,
- Benzersiz slug,
- Açıklama,
- Aktiflik durumu.

Slug küçük harf ve tire kullanılarak oluşturulmalı, sonradan değiştirilirken entegrasyon etkileri göz önünde bulundurulmalıdır. Kullanılmayan türler silinmek yerine pasifleştirilmelidir.

### 24.3. Hukuki Dosya Türleri

**Yönetim > Dosya Türleri** ekranında dosya türleri yönetilir.

Alanlar:

- Görünen ad,
- Ana kategori: Dava, İcra, Arabuluculuk veya Diğer,
- Açıklama.

Dosya türünün slug değeri oluşturulduktan sonra sabit kalır. Kullanılmayan türler pasifleştirilebilir.

### 24.4. Audit Kayıtları

Audit kayıtları yalnızca Yönetici tarafından görüntülenebilir.

Kayıtlar şu bilgilerle filtrelenebilir:

- Aksiyon,
- Kullanıcı,
- Olay,
- Başlangıç ve bitiş tarihi.

Audit detayında tarih, kullanıcı, IP adresi, tarayıcı bilgisi, ilgili kayıt ve değişiklik öncesi/sonrası değerler görülebilir.

Audit kayıtları sistemde kimin, hangi işlemi, ne zaman yaptığını izlemek içindir. Bu kayıtlar olağan kullanıcı işlemleriyle değiştirilemez.

**[Ekran görüntüsü 17: Kullanıcı yönetimi]**  
**[Ekran görüntüsü 18: Audit kaydı detayı]**

---

## 25. Hesap Güvenliği

### 25.1. İki Aşamalı Doğrulamayı Açma

1. Profil alanından **Hesap Güvenliği** ekranını açın.
2. Mevcut şifrenizi girin.
3. **İki Aşamalı Doğrulamayı Etkinleştir** düğmesine basın.
4. Sonraki girişte e-postanıza gönderilen kodu kullanın.

### 25.2. İki Aşamalı Doğrulamayı Kapatma

1. Güvenlik ekranını açın.
2. Mevcut şifrenizi girin.
3. Devre dışı bırakma işlemini onaylayın.

### 25.3. Temel Güvenlik Kuralları

- Şifrenizi ve doğrulama kodunuzu kimseyle paylaşmayın.
- Ortak cihazlarda **Beni hatırla** seçeneğini kullanmayın.
- Kullanmadığınız oturumdan çıkış yapın.
- Şüpheli erişim fark ederseniz yöneticinize bildirin ve şifrenizi değiştirin.
- Kişisel verileri yalnızca işin gerektirdiği ölçüde sisteme girin.
- İndirilen belgeleri yetkisiz kişilerle paylaşmayın.

---

## 26. Doğru Kullanım İçin Önerilen İş Akışları

### 26.1. Yeni Hukuki Talep Akışı

1. Çalışan veya Yönetici talebi oluşturur.
2. Talep uygun Avukata atanır.
3. Avukat talebi inceler ve ilk süreç güncellemesini girer.
4. Gerekli belgeler eklenir.
5. Talep gerekiyorsa hukuki dosyaya dönüştürülür.
6. Sonuçlanan talebin durumu güncellenir ve ardından kapatılır.

### 26.2. Yeni Hukuki Dosya Akışı

1. Önce müvekkil kaydı kontrol edilir veya oluşturulur.
2. Hukuki dosya açılır ve lider avukat belirlenir.
3. Mahkeme/icra ve taraf bilgileri girilir.
4. Evraklar uygun klasör ve türle yüklenir.
5. Duruşma, süre ve görevler takvime kaydedilir.
6. Tebligat, arabuluculuk ve iletişim kayıtları düzenli güncellenir.
7. Dosya sonuçlandığında durum sırasıyla Sonuçlandı ve Kapalı yapılır.

### 26.3. Evrak Güncelleme Akışı

1. Belgenin doğru dosyada olduğunu kontrol edin.
2. Yeni kopyayı mevcut belgenin **Yeni Sürüm** alanından yükleyin.
3. Sürüm notunda değişikliğin nedenini açıklayın.
4. Eski sürümü silmeye veya ayrı bir belge olarak tekrar yüklemeye çalışmayın.

---

## 27. Sık Karşılaşılan Durumlar

### “Bu işlem için yetkiniz yok” veya 403 sayfası

İlgili kayda erişiminiz bulunmuyor olabilir. Dosya atamanızı veya kullanıcı rolünüzü Yönetici ile kontrol edin.

### “Dosya başka bir kullanıcı tarafından güncellendi” uyarısı

Aynı kayıt başka bir kullanıcı tarafından değiştirilmiştir. Sayfayı yenileyin, güncel bilgileri kontrol edin ve işlemi yeniden yapın.

### Bir dosya veya müvekkil listede görünmüyor

- Filtreleri temizleyin.
- Dosyanın size atanmış olduğunu kontrol edin.
- Müvekkilin aktiflik durumunu kontrol edin.
- Gerekirse Yönetici ile iletişime geçin.

### UDF için “Dosya uzantısı desteklenmiyor” uyarısı

Dosya adının `.udf` ile bittiğini kontrol edin. `.udf.zip` veya çift uzantılı dosya kullanmayın.

### UDF için “Tek bir content.xml olmalıdır” uyarısı

Arşiv içinde birden fazla `content.xml` bulunuyor veya dosya yolu güvenli değil olabilir. UDF'yi resmî editörden yeniden dışa aktarın.

### UDF için “XML kök yapısı desteklenmiyor” uyarısı

Dosya genel bir XML arşivi olabilir veya desteklenen UYAP belge yapısına sahip olmayabilir. Gerçek UYAP UDF dosyası kullanın ya da belgeyi yalnızca indirme/arşiv amacıyla saklayın.

### CSV aktarımında satır hatası

Hata mesajındaki satır numarasını kaynak CSV'de bulun. Dosya türü, avukat e-postası, öncelik, tarih, yargı türü ve esas yılı alanlarını kontrol edin. Hatalar düzeltilmeden hiçbir satır aktarılmaz.

### Doğrulama kodu gelmiyor

E-posta adresinizi, spam klasörünü ve posta kutusu kotasını kontrol edin. **Tekrar gönder** seçeneğini kısa aralıklarla art arda kullanmayın. Sorun sürerse Yönetici ile iletişime geçin.

### Mobil menüde alt seçeneklere ulaşılamıyor

Menü içeriğini ekran üzerinde yukarı kaydırın. Tarayıcı eski arayüzü gösteriyorsa sayfayı yenileyin veya tarayıcı önbelleğini temizleyin.

---

## 28. Terimler Sözlüğü

| Terim | Açıklama |
|---|---|
| Hukuki Talep / Olay | Çalışan birimden hukuk ekibine iletilen ilk kayıt |
| Hukuki Dosya | Dava, icra, arabuluculuk veya diğer hukuk sürecinin ana kaydı |
| Lider Avukat | Hukuki dosyanın birincil sorumlu avukatı |
| Taraf | Müvekkil, davacı, davalı, alacaklı, borçlu veya diğer ilgili kişi/kurum |
| Süreç Güncellemesi | Hukuki talepte yapılan işlem veya gelişmenin tarihsel kaydı |
| Sanal Klasör | Evrakları dosya içinde gruplandıran sistem klasörü |
| Belge Sürümü | Aynı evrakın zaman içinde yüklenen değiştirilemez kopyası |
| Ters Kayıt | Hatalı finans hareketini silmeden etkisini tersine çeviren bağlantılı kayıt |
| UDF | UYAP Doküman Editörü tarafından kullanılan ZIP/XML tabanlı belge biçimi |
| Audit Kaydı | Kullanıcı işlemlerini tarih, kullanıcı ve değişiklik bilgileriyle izleyen sistem kaydı |
| İki Aşamalı Doğrulama | Şifreye ek olarak e-posta kodu isteyen güvenlik yöntemi |

---

## 29. PDF İçin Önerilen Ekran Görüntüsü Listesi

1. Giriş ekranı,
2. İki aşamalı doğrulama,
3. Dashboard,
4. Hukuki talepler listesi,
5. Talep detay ve süreç güncellemesi,
6. Hukuki dosya oluşturma,
7. Hukuki dosya detay ekranı,
8. Müvekkil detay ekranı,
9. Evrak listesi ve sürüm geçmişi,
10. UDF güvenli görüntüleme,
11. UDF düzenleyici,
12. Hukuki takvim,
13. Finans hareketleri,
14. Dosya devir ve atama talepleri,
15. Yönetim raporu,
16. UYAP CSV önizlemesi,
17. Kullanıcı yönetimi,
18. Audit detay ekranı,
19. Mobil menü görünümü.

Ekran görüntüsü alınırken gerçek müvekkil verileri, kimlik numaraları, e-posta adresleri ve dosya içerikleri kullanılmamalıdır. Eğitim için hazırlanmış anonim örnek veriler tercih edilmelidir.

---

## 30. Destek Bilgileri

**Uygulama adresi:** [Kurum uygulama adresi]  
**Teknik destek:** [Destek e-posta adresi]  
**Sistem sorumlusu:** [Birim / kişi bilgisi]  
**Doküman sürümü:** 1.0  
**Son güncelleme:** [Tarih]
