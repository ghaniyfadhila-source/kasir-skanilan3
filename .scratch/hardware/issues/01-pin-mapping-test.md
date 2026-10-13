# 01: Finalize pin mapping & test wiring

**What to build:** Verified pin mapping for NodeMCU v3, all hardware components working.

**Blocked by:** None (can start immediately)

**Status:** ready-for-agent

- [ ] `firmware/include/config.h` pin definitions finalized:
  - RC522: SS=D8(15), SCK=D5(14), MOSI=D7(13), MISO=D6(12), RST=D3(0) with 10k pull-up
  - LCD I2C: SDA=D2(4), SCL=D1(5), addr=0x27 (or 0x3F), 16x2
  - Buzzer: D0(16)
  - LED: built-in D4(2) for status
- [ ] Physical wiring matches pin mapping (photo documented)
- [ ] Test sketch: RFID reads UID correctly (test with 3+ cards)
- [ ] Test sketch: LCD displays "Hello World" on both rows
- [ ] Test sketch: Buzzer beeps on command
- [ ] Test sketch: Built-in LED blinks/indicates status
- [ ] All tests pass simultaneously (no pin conflicts)
- [ ] Update `config.h` with verified values