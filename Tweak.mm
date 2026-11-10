#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

// Projedeki diger dosyalari ve siniflari dahil et
#import "Menux/Engine.mm"
#import "Menux/Menu.mm"
#import "View/FloatView.h"
#import "KeySystem.mm"

%ctor {
    // 1. Oyun acildiktan 5 saniye sonra Key ekranini goster
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        showKeyMenu(); 
    });
    
    // 2. Key dogrulandiginda butonu (FloatView) ekrana ekle
    [[NSNotificationCenter defaultCenter] addObserverForName:@"StartHack" object:nil queue:[NSOperationQueue mainQueue] usingBlock:^(NSNotification * _Nonnull note) {
        
        // MenuControl sinifini baslat (Menu.mm icinde tanimli olmali)
        ModuleControl *control = [[ModuleControl alloc] init]; 
        
        // Butonu olustur ve ekrana ekle
        FloatView *floatBtn = [FloatView sharedInstance:control];
        floatBtn.frame = CGRectMake(10, 150, 60, 60); 
        
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            [window addSubview:floatBtn];
            NSLog(@"[M7MD] Key dogrulandi, buton basariyla eklendi!");
        }
    }];
}
