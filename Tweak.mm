#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>
#import "KeySystem.mm"

%ctor {
    // Sadece Key ekranını aç, Engine.mm'yi çağırma!
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(3.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        showKeyMenu(); 
    });
}
