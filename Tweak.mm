#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

// --- PROJEDEKI DIGER DOSYALARI DAHIL ET ---
#import "Menux/Engine.mm"
#import "Menux/Menu.mm"
#import "View/FloatView.h"
#import "KeySystem.mm"

%ctor {
    // Oyunun ana penceresinin hazir olmasini bekle
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        
        NSLog(@"[M7MD] Key menusu aciliyor...");
        
        // 1. Key ekranini ac
        showKeyMenu(); 
        
        // 2. Key dogrulandiginda butonu ekle (Notification Center ile dinle)
        [[NSNotificationCenter defaultCenter] addObserverForName:@"StartHack" object:nil queue:[NSOperationQueue mainQueue] usingBlock:^(NSNotification * _Nonnull note) {
            
            NSLog(@"[M7MD] Key dogrulandi! Buton ekleniyor...");
            
            // ModuleControl'u baslat
            ModuleControl *control = [[ModuleControl alloc] init]; 
            
            // Butonu olustur
            FloatView *floatBtn = [FloatView sharedInstance:control];
            floatBtn.frame = CGRectMake(10, 150, 60, 60); 
            
            // Oyunun penceresine ekle
            UIWindow *window = [UIApplication sharedApplication].keyWindow;
            if (window) {
                [window addSubview:floatBtn];
                NSLog(@"[M7MD] Buton basariyla eklendi!");
            }
        }];
    });
}
