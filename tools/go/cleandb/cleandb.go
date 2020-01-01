/*@Author: 0wew0
 * @Date: 2020-03-03 17:44:13
 * @LastEditTime: 2020-03-03 17:45:15
 * @LastEditors: 0wew0
 * @Description: In User Settings Edit
 * @FilePath: /zyz/user/tools/go/cleandb/cleandb.go
 */
package main

import (
	"database/sql"
	"flag"
	"fmt"
	"time"

	//导入mysql的驱动
	_ "github.com/go-sql-driver/mysql"
)

var cdbtime int64
var cdbcount int
var cdbrow int

func main() {
	flag.Int64Var(&cdbtime, "t", 0, "指定时间戳")
	flag.IntVar(&cdbcount, "c", 1, "指定需要执行的次数")
	flag.IntVar(&cdbrow, "r", 10, "指定删除多少行")
	flag.Parse()
	//使用database/sql包中的Open连接数据库
	db, err := sql.Open("mysql", "test:H!keP@X1V7A799AG@tcp(localhost:3306)/test?charset=utf8")
	check(err)

	// // 执行添加语句
	// for i := 0; i < 900; i++ {
	// 	rows, err := db.Exec("INSERT INTO test(id,name,context)VALUES (?,?,?)", nil, time.Now().Unix(), time.Now().Unix())
	// 	check(err)
	// 	//获取修改的行数
	// 	id, err := rows.LastInsertId()
	// 	check(err)
	// 	fmt.Println(id)
	// 	time.Sleep(1000000000)
	// }
	// //关闭连接
	// defer db.Close()

	for i := 0; i < cdbcount; i++ {
		// fmt.Println("time:", cdbtime)
		// fmt.Println("time:", time.Unix(cdbtime, 0).Format("2006-01-02 15:04:05"))
		// fmt.Println("count:", cdbcount)
		// fmt.Println("row:", cdbrow)
		var rtrowscount = selectdb(db)
		if rtrowscount == 0 {
			break
		} else {
			deletedb(db)
			// fmt.Println("Delete")
		}
		time.Sleep(1000000000)
	}
	//关闭连接
	defer db.Close()
	fmt.Println("done")
	// //关闭连接
	// defer db.Close()
}

func selectdb(db *sql.DB) int {
	// 执行查询语句
	// var timer int64 = 1583233409
	rows, err := db.Query("select count(id) from `test` WHERE `time` <=? ORDER BY `time` ASC LIMIT ?", time.Unix(cdbtime, 0).Format("2006-01-02 15:04:05"), cdbrow)
	check(err)
	//遍历查询的结果集合
	for rows.Next() {
		var cleanid int
		//将从数据库中查询到的值对应到结构体中相应的变量中
		err = rows.Scan(&cleanid)
		check(err)
		fmt.Println("根据条件找到了一行:id=", cleanid)
		return cleanid
	}
	return 0
}

func deletedb(db *sql.DB) {
	// 执行删除语句
	result, err := db.Exec("DELETE from `test` WHERE `time` <=? ORDER BY `time` ASC LIMIT ?", time.Unix(cdbtime, 0).Format("2006-01-02 15:04:05"), cdbrow)
	check(err)
	//获取删除的行数
	dels, err := result.RowsAffected()
	check(err)
	fmt.Println("删除了", dels, "行")
}

//对错误检查的封装
func check(err error) {
	if err != nil {
		fmt.Println(err)
	}
}
