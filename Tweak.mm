#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

// Eski kodlardan kalan hicbir sey olmasin
// #import "KeySystem.mm"
// #import "View/FloatView.h" 

%ctor {
    // Sadece ekrana yazi yazdiralim, baska hicbir sey yapmayalim
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(0, 100, window.bounds.size.width, 50)];
            label.text = @"DARK IOS - TEST BASARILI!";
            label.textColor = [UIColor greenColor];
            label.textAlignment = NSTextAlignmentCenter;
            label.font = [UIFont boldSystemFontOfSize:24];
            label.backgroundColor = [UIColor blackColor];
            label.tag = 9999;
            [window addSubview:label];
        }
    });
}
