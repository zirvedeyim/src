#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>
#import <WebKit/WebKit.h>

// 1. Key Doğrulama Fonksiyonu (Sunucu Bağlantısı)
BOOL validateKey(NSString *enteredKey) {
    NSString *urlString = [NSString stringWithFormat:@"https://a-g9eo.onrender.com/api.php?key=%@", enteredKey];
    NSURL *url = [NSURL URLWithString:urlString];
    NSString *response = [NSString stringWithContentsOfURL:url encoding:NSUTF8StringEncoding error:nil];
    if (response && ([response containsString:@"success"] || [response containsString:@"true"])) {
        return YES;
    }
    return NO;
}

// 2. Key Ekranını Yöneten Sınıf
@interface KeyViewController : UIViewController <WKScriptMessageHandler>
@property (nonatomic, strong) WKWebView *webView;
@end

@implementation KeyViewController

- (void)viewDidLoad {
    [super viewDidLoad];
    
    WKWebViewConfiguration *config = [[WKWebViewConfiguration alloc] init];
    WKUserContentController *userContentController = [[WKUserContentController alloc] init];
    [userContentController addScriptMessageHandler:self name:@"keySubmit"];
    config.userContentController = userContentController;
    
    self.webView = [[WKWebView alloc] initWithFrame:self.view.bounds configuration:config];
    self.webView.autoresizingMask = UIViewAutoresizingFlexibleWidth | UIViewAutoresizingFlexibleHeight;
    [self.view addSubview:self.webView];
    
    self.view.backgroundColor = [UIColor blackColor];
    self.webView.backgroundColor = [UIColor blackColor];
    self.webView.opaque = NO;
    
    // HTML Tasarımı
    NSString *htmlString = @"<!DOCTYPE html><html lang='en'><head>"
    "<meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>"
    "<title>DARK IOS</title>"
    "<style>"
    "* { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, sans-serif; }"
    "body { background-color: #000; height: 100vh; display: flex; justify-content: center; align-items: center; color: #fff; }"
    ".key-container { background: rgba(20, 20, 25, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 20px; padding: 40px 30px; width: 90%; max-width: 400px; text-align: center; backdrop-filter: blur(10px); }"
    ".logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }"
    ".subtitle { font-size: 14px; color: #888; margin-bottom: 30px; }"
    "input { width: 100%; padding: 15px; background: #111; border: 1px solid #333; border-radius: 10px; color: #fff; font-size: 16px; margin-bottom: 20px; outline: none; }"
    "button { width: 100%; padding: 15px; background: #fff; color: #000; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; }"
    ".error { color: #ff4444; margin-top: 15px; display: none; font-size: 14px; }"
    "</style></head><body>"
    "<div class='key-container'>"
    "<div class='logo'>DARK IOS</div>"
    "<div class='subtitle'>Please enter your license key</div>"
    "<input type='text' id='licenseKey' placeholder='DARKIOS-XXXX-XXXX'>"
    "<button onclick='submitKey()'>Login</button>"
    "<div class='error' id='errorMsg'>Invalid Key!</div>"
    "</div>"
    "<script>"
    "function submitKey() {"
    "var key = document.getElementById('licenseKey').value;"
    "window.webkit.messageHandlers.keySubmit.postMessage(key);"
    "}"
    "function showError() { document.getElementById('errorMsg').style.display = 'block'; }"
    "</script></body></html>";
    
    [self.webView loadHTMLString:htmlString baseURL:nil];
    
    // 30 Saniye Zaman Aşımı (Boş durursa at)
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(30.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        if (self.presentingViewController) {
            [self dismissViewControllerAnimated:YES completion:nil];
            exit(0); 
        }
    });
}

// 3. JavaScript'ten Gelen Key'i Yakala
- (void)userContentController:(WKUserContentController *)userContentController didReceiveScriptMessage:(WKScriptMessage *)message {
    if ([message.name isEqualToString:@"keySubmit"]) {
        NSString *enteredKey = message.body;
        
        dispatch_async(dispatch_get_main_queue(), ^{
            if (validateKey(enteredKey)) {
                // Key Doğruysa Ekranı Kapat
                [self dismissViewControllerAnimated:YES completion:nil];
            } else {
                // Key Yanlışsa HTML'deki hatayı göster
                [self.webView evaluateJavaScript:@"showError()" completionHandler:nil];
            }
        });
    }
}
@end

// 4. Ana Başlatıcı (Oyun açılınca çalışır)
__attribute__((constructor)) static void initialize() {
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(5.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        
        // iOS 13+ uyumlu pencere bulma yöntemi (keyWindow hatasını çözer)
        UIWindow *window = nil;
        if (@available(iOS 13.0, *)) {
            for (UIScene *scene in [UIApplication sharedApplication].connectedScenes) {
                if ([scene isKindOfClass:[UIWindowScene class]]) {
                    UIWindowScene *windowScene = (UIWindowScene *)scene;
                    for (UIWindow *w in windowScene.windows) {
                        if (w.isKeyWindow) {
                            window = w;
                            break;
                        }
                    }
                }
            }
        }
        
        if (!window) {
            window = [UIApplication sharedApplication].windows.firstObject;
        }
        
        if (window) {
            NSLog(@"[M7MD] Pencere bulundu, Key ekrani aciliyor...");
            KeyViewController *webVC = [[KeyViewController alloc] init];
            webVC.modalPresentationStyle = UIModalPresentationFullScreen;
            [window.rootViewController presentViewController:webVC animated:YES completion:nil];
        } else {
            NSLog(@"[M7MD] Pencere bulunamadi!");
        }
    });
}
