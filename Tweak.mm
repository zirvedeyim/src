#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

// --- PROJEDEKI DIGER DOSYALARI DAHIL ET ---
// Bu satirlar, Menu, Engine ve KeySystem kodlarinin derlemeye dahil olmasini saglar.
#import "Menux/Engine.mm"
#import "Menux/Menu.mm"
#import "View/FloatView.h"
#import "KeySystem.mm"

// --- ANA BASLATICI (CONSTRUCTOR) ---
%ctor {
    // 1. Oyun acildiktan 3 saniye sonra Key Menusunu (WebView) ac
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(3.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        showKeyMenu(); 
    });
    
    // 2. Key dogrulandiginda (StartHack sinyali geldiginde) Butonu (FloatView) ekrana ekle
    [[NSNotificationCenter defaultCenter] addObserverForName:@"StartHack" object:nil queue:[NSOperationQueue mainQueue] usingBlock:^(NSNotification * _Nonnull note) {
        
        // ModuleControl sinifini baslat
        ModuleControl *control = [[ModuleControl alloc] init]; 
        
        // Butonu olustur
        FloatView *floatBtn = [FloatView sharedInstance:control];
        
        // Butonun ekrandaki boyutu ve yeri (Sol ustte 60x60)
        floatBtn.frame = CGRectMake(10, 150, 60, 60); 
        
        // Oyunun ana penceresine butonu ekle
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            [window addSubview:floatBtn];
            NSLog(@"[M7MD] Key dogrulandi, buton basariyla eklendi!");
        } else {
            NSLog(@"[M7MD] Pencere bulunamadi, buton eklenemedi!");
        }
    }];
}
