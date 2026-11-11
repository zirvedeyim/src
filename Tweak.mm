#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>
#import "KeySystem.mm"

%ctor {
    // Oyunun ana penceresinin hazir olmasi icin cok kisa bir sure (1 saniye) bekle
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(1.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        
        // Key ekranini ac
        showKeyMenu(); 
        
    });
    
    // Key dogrulandiginda butonu (FloatView) ekle
    [[NSNotificationCenter defaultCenter] addObserverForName:@"StartHack" object:nil queue:[NSOperationQueue mainQueue] usingBlock:^(NSNotification * _Nonnull note) {
        
        ModuleControl *control = [[ModuleControl alloc] init]; 
        FloatView *floatBtn = [FloatView sharedInstance:control];
        floatBtn.frame = CGRectMake(10, 150, 60, 60); 
        
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            [window addSubview:floatBtn];
        }
    }];
}
