#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

// KeySystem.mm'nin icindeki fonksiyonlari disaridan cagirabilmek icin tanitiyoruz.
// .mm dosyasini import etmiyoruz, boylece duplicate (cift) derleme hatasi cozuluyor.
extern void showKeyMenu(void);

__attribute__((constructor)) static void initialize() {
    // Oyun acildiktan 5 saniye sonra Key ekranini ac
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        
        NSLog(@"[M7MD] TEST: Key Sistemi Baslatiliyor...");
        
        // Key sistemini ac
        showKeyMenu(); 
    });
}
