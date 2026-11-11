#import <UIKit/UIKit.h>

__attribute__((constructor)) static void init() {
    NSLog(@"[TEST] Tweak oyuna basariyla yuklendi!");
}
