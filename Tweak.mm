#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>
#import "KeySystem.mm"

// FloatView ve MenuControl hatalarini simdilik kaldirdik
// #import "View/FloatView.h" 
// #import "Menu.h"

%ctor {
    // 1. Test: Dylib oyuna giriyor mu?
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(0, 100, window.bounds.size.width, 50)];
            label.text = @"DARK IOS - KOD CALISIYOR!";
            label.textColor = [UIColor greenColor];
            label.textAlignment = NSTextAlignmentCenter;
            label.font = [UIFont boldSystemFontOfSize:24];
            label.backgroundColor = [UIColor blackColor];
            [window addSubview:label];
        }
    });

    // 2. Key Ekrani (Test yazisi cikarsa burayi acacagiz)
    /*
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(3.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        showKeyMenu(); 
    });
    */
}
