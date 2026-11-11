#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>
#import "KeySystem.mm"

// %ctor yerine Theos'un C++ uyumlu başlatıcısını kullanıyoruz
__attribute__((constructor)) static void initialize() {
    // Oyun açıldıktan 5 saniye sonra Key ekranını aç
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        
        NSLog(@"[M7MD] TEST: Sadece Key Sistemi Başlatılıyor...");
        
        // Key sistemini aç
        showKeyMenu(); 
    });
}
