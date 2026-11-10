#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

%ctor {
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(0, 100, window.bounds.size.width, 50)];
            label.text = @"TEST BASARILI - DYLIB CALISIYOR!";
            label.textColor = [UIColor greenColor];
            label.textAlignment = NSTextAlignmentCenter;
            label.font = [UIFont boldSystemFontOfSize:20];
            label.backgroundColor = [UIColor blackColor];
            [window addSubview:label];
        }
    });
}
