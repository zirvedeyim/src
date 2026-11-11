#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>

__attribute__((constructor)) static void initialize() {
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (window) {
            UILabel *label = [[UILabel alloc] initWithFrame:CGRectMake(0, 100, window.bounds.size.width, 50)];
            label.text = @"ENJEKSIYON BASARILI!";
            label.textColor = [UIColor greenColor];
            label.backgroundColor = [UIColor blackColor];
            label.textAlignment = NSTextAlignmentCenter;
            label.font = [UIFont boldSystemFontOfSize:24];
            [window addSubview:label];
        }
    });
}
