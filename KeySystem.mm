#import <UIKit/UIKit.h>
#import <Foundation/Foundation.h>
#import <WebKit/WebKit.h>

// Cihazın benzersiz ID'sini almak için
NSString* getDeviceID() {
    NSString *uuid = [[[UIDevice currentDevice] identifierForVendor] UUIDString];
    return uuid ? uuid : @"unknown_device";
}

// Cihaz modelini almak için (Örn: iPhone 13 Pro)
NSString* getDeviceModelName() {
    NSString *model = [[UIDevice currentDevice] model];
    NSString *name = [[UIDevice currentDevice] name];
    return [NSString stringWithFormat:@"%@ (%@)", model, name];
}

// Pil seviyesini almak için (Örn: %85)
NSString* getBatteryLevel() {
    [UIDevice currentDevice].batteryMonitoringEnabled = YES;
    float batteryLevel = [[UIDevice currentDevice] batteryLevel];
    if (batteryLevel < 0) return @"Unknown";
    return [NSString stringWithFormat:@"%d%%", (int)(batteryLevel * 100)];
}

// api.php'ye bağlanıp gelen cevabı okuyan fonksiyon
NSDictionary* validateKeyFromServer(NSString *enteredKey) {
    NSString *urlString = @"https://a-g9eo.onrender.com/api.php";
    NSURL *url = [NSURL URLWithString:urlString];
    
    NSMutableURLRequest *request = [NSMutableURLRequest requestWithURL:url];
    [request setHTTPMethod:@"POST"];
    [request setValue:@"application/json" forHTTPHeaderField:@"Content-Type"];
    
    NSDictionary *jsonBody = @{
        @"key": enteredKey,
        @"device_id": getDeviceID(),
        @"device_model": getDeviceModelName(),
        @"battery": getBatteryLevel() // Pil bilgisini de yolluyoruz
    };
    
    NSError *error;
    NSData *jsonData = [NSJSONSerialization dataWithJSONObject:jsonBody options:0 error:&error];
    [request setHTTPBody:jsonData];
    
    NSData *responseData = [NSURLConnection sendSynchronousRequest:request returningResponse:nil error:&error];
    
    if (error || !responseData) {
        return @{@"status": @"error", @"message": @"Server connection failed."};
    }
    
    NSDictionary *responseDict = [NSJSONSerialization JSONObjectWithData:responseData options:0 error:&error];
    return responseDict;
}

static NSString *globalActiveKey = nil;
static NSTimer *globalHeartbeatTimer = nil;

void startHeartbeatCheck() {
    if (globalHeartbeatTimer) {
        [globalHeartbeatTimer invalidate];
        globalHeartbeatTimer = nil;
    }
    
    // 30 saniyede bir kontrol
    globalHeartbeatTimer = [NSTimer scheduledTimerWithTimeInterval:30.0 repeats:YES block:^(NSTimer * _Nonnull timer) {
        if (!globalActiveKey) return;
        
        dispatch_async(dispatch_get_global_queue(DISPATCH_QUEUE_PRIORITY_DEFAULT, 0), ^{
            NSDictionary *response = validateKeyFromServer(globalActiveKey);
            NSString *status = response[@"status"];
            
            if (![status isEqualToString:@"success"]) {
                dispatch_async(dispatch_get_main_queue(), ^{
                    NSLog(@"[M7MD] Key artik gecersiz! Oyundan atiliyor...");
                    exit(0); 
                });
            }
        });
    }];
}

@interface KeyWebViewController : UIViewController <WKScriptMessageHandler>
@property (nonatomic, strong) WKWebView *webView;
@end

@implementation KeyWebViewController

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
    self.webView.scrollView.scrollEnabled = NO;
    
    NSString *htmlString = @"<!DOCTYPE html>"
    "<html lang='en'>"
    "<head>"
    "<meta charset='UTF-8'>"
    "<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>"
    "<title>DARK IOS - License Verification</title>"
    "<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap' rel='stylesheet'>"
    "<style>"
    "* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }"
    "body { background-color: #000000; background-image: radial-gradient(circle at 50% 0%, #1a1a2e 0%, #000000 70%); height: 100vh; display: flex; justify-content: center; align-items: center; color: #ffffff; overflow: hidden; }"
    ".key-container { background: rgba(20, 20, 25, 0.7); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 24px; padding: 40px 30px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); position: relative; overflow: hidden; animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1); }"
    ".key-container::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); }"
    ".logo { font-size: 28px; font-weight: 700; letter-spacing: 2px; margin-bottom: 8px; background: linear-gradient(90deg, #ffffff, #a0a0a0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }"
    ".subtitle { font-size: 13px; color: #888; margin-bottom: 30px; }"
    ".input-group { position: relative; margin-bottom: 20px; }"
    ".input-group input { width: 100%; padding: 16px 20px; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; color: #fff; font-size: 16px; outline: none; transition: all 0.3s ease; }"
    ".input-group input::placeholder { color: #555; }"
    ".input-group input:focus { border-color: rgba(255, 255, 255, 0.4); background: rgba(0, 0, 0, 0.6); box-shadow: 0 0 15px rgba(255, 255, 255, 0.05); }"
    ".submit-btn { width: 100%; padding: 16px; background: #ffffff; color: #000000; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 10px; }"
    ".submit-btn:active { transform: scale(0.98); }"
    ".submit-btn:hover { box-shadow: 0 0 20px rgba(255, 255, 255, 0.2); }"
    ".buy-key-container { text-align: left; margin-top: 15px; padding-left: 5px; }"
    ".buy-key-btn { display: inline-block; color: #60a5fa; font-size: 13px; font-weight: 600; text-decoration: none; padding: 6px 12px; border: 1px solid rgba(96, 165, 250, 0.3); border-radius: 8px; background: rgba(96, 165, 250, 0.05); transition: all 0.3s ease; letter-spacing: 0.5px; }"
    ".buy-key-btn:active { transform: scale(0.95); background: rgba(96, 165, 250, 0.15); }"
    ".loader { display: none; width: 20px; height: 20px; border: 3px solid rgba(0,0,0,0.2); border-radius: 50%; border-top-color: #000; animation: spin 1s ease-in-out infinite; margin: 0 auto; }"
    ".error-msg { display: none; color: #ff4444; font-size: 14px; margin-top: 15px; font-weight: 500; animation: shake 0.4s; }"
    "@keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }"
    "@keyframes spin { to { transform: rotate(360deg); } }"
    "@keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }"
    "</style>"
    "</head>"
    "<body>"
    "<div class='key-container'>"
    "<div class='logo'>DARK IOS</div>"
    "<div class='subtitle'>Please enter your license key</div>"
    "<form id='keyForm' onsubmit='submitKey(event)'>"
    "<div class='input-group'>"
    "<input type='text' id='licenseKey' placeholder='DARKIOS-XXXX-XXXX-XXXX' autocomplete='off' required>"
    "</div>"
    "<button type='submit' class='submit-btn' id='submitBtn'>"
    "<span id='btnText'>Login</span>"
    "<div class='loader' id='loader'></div>"
    "</button>"
    "</form>"
    "<div class='buy-key-container'>"
    "<a href='https://t.me/dylibsafe' target='_blank' class='buy-key-btn'>BUY KEY</a>"
    "</div>"
    "<div class='error-msg' id='errorMsg'></div>"
    "</div>"
    "<script>"
    "function submitKey(event) {"
    "event.preventDefault();"
    "const keyInput = document.getElementById('licenseKey').value;"
    "const btnText = document.getElementById('btnText');"
    "const loader = document.getElementById('loader');"
    "const errorMsg = document.getElementById('errorMsg');"
    "errorMsg.style.display = 'none';"
    "btnText.style.display = 'none';"
    "loader.style.display = 'block';"
    "if (window.webkit && window.webkit.messageHandlers && window.webkit.messageHandlers.keySubmit) {"
    "window.webkit.messageHandlers.keySubmit.postMessage(keyInput);"
    "}"
    "}"
    "function showError(msg) {"
    "const btnText = document.getElementById('btnText');"
    "const loader = document.getElementById('loader');"
    "const errorMsg = document.getElementById('errorMsg');"
    "btnText.style.display = 'block';"
    "loader.style.display = 'none';"
    "errorMsg.innerText = msg;"
    "errorMsg.style.display = 'block';"
    "}"
    "</script>"
    "</body>"
    "</html>";
    
    [self.webView loadHTMLString:htmlString baseURL:nil];
    
    dispatch_after(dispatch_time(DISPATCH_TIME_NOW, (int64_t)(30.0 * NSEC_PER_SEC)), dispatch_get_main_queue(), ^{
        if (self.presentingViewController) {
            [self dismissViewControllerAnimated:YES completion:nil];
            exit(0); 
        }
    });
}

- (void)userContentController:(WKUserContentController *)userContentController didReceiveScriptMessage:(WKScriptMessage *)message {
    if ([message.name isEqualToString:@"keySubmit"]) {
        NSString *enteredKey = message.body;
        
        dispatch_async(dispatch_get_main_queue(), ^{
            NSDictionary *response = validateKeyFromServer(enteredKey);
            NSString *status = response[@"status"];
            NSString *msg = response[@"message"];
            
            if ([status isEqualToString:@"success"]) {
                globalActiveKey = enteredKey; 
                [self dismissViewControllerAnimated:YES completion:^{
                    [[NSNotificationCenter defaultCenter] postNotificationName:@"StartHack" object:nil];
                    startHeartbeatCheck();
                }];
            } else {
                NSString *errorString = @"Key not found.";
                
                if ([msg containsString:@"bloklandi"] || [msg containsString:@"banned"]) {
                    errorString = @"Key banned.";
                } else if ([msg containsString:@"vaxti bitib"] || [msg containsString:@"expire"]) {
                    errorString = @"Key expired.";
                } else if ([msg containsString:@"movcud deyil"] || [msg containsString:@"silinmisdir"]) {
                    errorString = @"Key deleted.";
                } else if ([msg containsString:@"cihazda aktivdir"] || [msg containsString:@"limit"]) {
                    errorString = @"Key active on another device or limit reached.";
                }
                
                NSString *jsString = [NSString stringWithFormat:@"showError('%@');", errorString];
                [self.webView evaluateJavaScript:jsString completionHandler:nil];
            }
        });
    }
}
@end

void showKeyMenu(void) {
    dispatch_async(dispatch_get_main_queue(), ^{
        UIWindow *window = [UIApplication sharedApplication].keyWindow;
        if (!window) return;
        
        KeyWebViewController *webVC = [[KeyWebViewController alloc] init];
        webVC.modalPresentationStyle = UIModalPresentationFullScreen;
        [window.rootViewController presentViewController:webVC animated:YES completion:nil];
    });
}
